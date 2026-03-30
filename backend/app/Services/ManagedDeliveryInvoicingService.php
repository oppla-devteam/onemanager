<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagedDeliveryInvoicingService
{
    private const DEFAULT_VAT = 22;

    /**
     * Pre-genera fatture per consegne gestite non fatturate.
     */
    public function pregenerate(array $filters): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);
        $periodLabel = $this->buildPeriodLabel($startDate, $endDate, $filters['period'] ?? null);

        $deliveries = $this->buildQuery($filters, $startDate, $endDate)->get();
        $grouped = $deliveries->groupBy('client_id');

        $previews = [];

        foreach ($grouped as $clientId => $clientDeliveries) {
            $client = $clientDeliveries->first()?->client;
            $totalCents = $clientDeliveries->sum(fn(Delivery $d) => $this->getDeliveryFeeCents($d));
            $totalAmount = round($totalCents / 100, 2);
            $deliveryCount = $clientDeliveries->count();

            $error = null;
            if (!$client) {
                $error = 'Cliente non associato (oppla_external_id mancante)';
            } elseif ($totalAmount <= 0) {
                $error = 'Importo totale nullo o negativo';
            }

            $existingInvoice = null;
            if ($client && $totalAmount > 0) {
                $existingInvoice = $this->findExistingInvoice($client->id, $periodLabel);
            }

            $previews[] = [
                'client_id' => $client?->id,
                'client_name' => $client?->ragione_sociale ?? 'Cliente non associato',
                'deliveries_count' => $deliveryCount,
                'total_amount' => $totalAmount,
                'invoice_ready' => $client !== null && $totalAmount > 0 && $existingInvoice === null,
                'already_generated' => $existingInvoice !== null,
                'existing_invoice_id' => $existingInvoice?->id,
                'existing_invoice_number' => $existingInvoice?->numero_fattura,
                'error' => $error,
            ];
        }

        return [
            'previews' => $previews,
            'total_invoices' => count($previews),
            'ready_invoices' => count(array_filter($previews, fn($p) => $p['invoice_ready'] ?? false)),
            'period' => [
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'label' => $periodLabel,
            ],
        ];
    }

    /**
     * Genera fatture per consegne gestite non fatturate.
     */
    public function generate(array $filters): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);
        $periodLabel = $this->buildPeriodLabel($startDate, $endDate, $filters['period'] ?? null);

        $query = $this->buildQuery($filters, $startDate, $endDate);

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        $deliveries = $query->get();
        $grouped = $deliveries->groupBy('client_id');

        $invoices = [];
        $errors = [];
        $totalAmount = 0;

        foreach ($grouped as $clientId => $clientDeliveries) {
            $client = $clientDeliveries->first()?->client;

            if (!$client) {
                $errors[] = [
                    'client_id' => null,
                    'message' => 'Cliente non associato per alcune consegne',
                ];
                continue;
            }

            $existingInvoice = $this->findExistingInvoice($client->id, $periodLabel);
            if ($existingInvoice) {
                $errors[] = [
                    'client_id' => $client->id,
                    'message' => 'Fattura gia generata per questo periodo',
                    'invoice_id' => $existingInvoice->id,
                ];
                continue;
            }

            $totalCents = $clientDeliveries->sum(fn(Delivery $d) => $this->getDeliveryFeeCents($d));
            $totalGross = round($totalCents / 100, 2);

            if ($totalGross <= 0) {
                $errors[] = [
                    'client_id' => $client->id,
                    'message' => 'Importo totale nullo o negativo',
                ];
                continue;
            }

            $deliveryCount = $clientDeliveries->count();
            $imponibile = round($totalGross / (1 + (self::DEFAULT_VAT / 100)), 2);
            $iva = round($totalGross - $imponibile, 2);

            $emissionDate = $endDate ? $endDate->copy()->endOfDay() : now();
            $dueDate = $emissionDate->copy()->addDays(30);
            $causale = "Consegne Gestite - Periodo {$periodLabel}";

            try {
                $invoice = DB::transaction(function () use ($client, $imponibile, $iva, $totalGross, $emissionDate, $dueDate, $causale, $deliveryCount, $periodLabel, $clientDeliveries) {
                    $invoice = new Invoice([
                        'client_id' => $client->id,
                        'type' => 'attiva',
                        'invoice_type' => 'differita',
                        'anno' => null,
                        'data_emissione' => $emissionDate,
                        'data_scadenza' => $dueDate,
                        'imponibile' => $imponibile,
                        'iva' => $iva,
                        'totale' => $totalGross,
                        'status' => 'bozza',
                        'payment_status' => 'non_pagata',
                        'payment_method' => 'bonifico',
                        'causale' => $causale,
                        'note' => "Fattura consegne gestite - {$deliveryCount} consegne - Periodo {$periodLabel}",
                    ]);

                    $invoice->generateInvoiceNumber();
                    $invoice->save();

                    $descrizione = "Consegne gestite - {$deliveryCount} consegne - Periodo {$periodLabel}";

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'descrizione' => $descrizione,
                        'quantita' => 1,
                        'prezzo_unitario' => $imponibile,
                        'iva_percentuale' => self::DEFAULT_VAT,
                        'subtotale' => $imponibile,
                        'service_type' => 'delivery',
                        'service_id' => (string) $deliveryCount,
                    ]);

                    $invoice->refresh();
                    $invoice->calculateTotals();

                    Delivery::whereIn('id', $clientDeliveries->pluck('id'))->update([
                        'is_invoiced' => true,
                        'invoice_id' => $invoice->id,
                    ]);

                    return $invoice;
                });

                $invoices[] = [
                    'id' => $invoice->id,
                    'numero_fattura' => $invoice->numero_fattura,
                    'client_id' => $invoice->client_id,
                    'client_name' => $client->ragione_sociale,
                    'totale' => $invoice->totale,
                    'status' => $invoice->status,
                ];

                $totalAmount += $invoice->totale;
            } catch (\Exception $e) {
                Log::error('[ManagedDeliveryInvoicing] Errore generazione fattura', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage(),
                ]);

                $errors[] = [
                    'client_id' => $client->id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'invoices' => $invoices,
            'count' => count($invoices),
            'total_amount' => round($totalAmount, 2),
            'errors' => $errors,
            'period' => [
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
                'label' => $periodLabel,
            ],
        ];
    }

    private function buildQuery(array $filters, ?Carbon $startDate, ?Carbon $endDate)
    {
        $query = Delivery::with('client')
            ->whereNull('invoice_id')
            ->where(function ($q) {
                $q->where('is_invoiced', false)->orWhereNull('is_invoiced');
            });

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('order_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('order_date', '<=', $endDate);
        }

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['Completed', 'completata', 'delivered', 'Delivered']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        return $query;
    }

    private function resolveDateRange(array $filters): array
    {
        $startDate = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;

        if ($startDate || $endDate) {
            return [$startDate, $endDate];
        }

        $period = $filters['period'] ?? 'all';

        switch ($period) {
            case 'today':
                return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            case 'week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'year':
                return [now()->startOfYear(), now()->endOfYear()];
            case 'last_month':
                $start = now()->subMonth()->startOfMonth();
                $end = now()->subMonth()->endOfMonth();
                return [$start, $end];
            case 'last_year':
                return [now()->subYear()->startOfDay(), now()->endOfDay()];
            case 'all':
            default:
                return [null, null];
        }
    }

    private function buildPeriodLabel(?Carbon $startDate, ?Carbon $endDate, ?string $period): string
    {
        if ($startDate && $endDate) {
            return $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d');
        }

        if ($startDate) {
            return 'dal ' . $startDate->format('Y-m-d');
        }

        if ($endDate) {
            return 'fino al ' . $endDate->format('Y-m-d');
        }

        if ($period && $period !== 'all') {
            return $period;
        }

        return 'tutte le date';
    }

    private function getDeliveryFeeCents(Delivery $delivery): int
    {
        $fee = $delivery->delivery_fee_total;

        if ($fee === null) {
            $fee = ($delivery->platform_fee ?? 0) + ($delivery->distance_fee ?? 0);
        }

        return (int) round((float) $fee);
    }

    private function findExistingInvoice(int $clientId, string $periodLabel): ?Invoice
    {
        return Invoice::where('client_id', $clientId)
            ->where('type', 'attiva')
            ->where('invoice_type', 'differita')
            ->where('causale', "Consegne Gestite - Periodo {$periodLabel}")
            ->first();
    }
}
