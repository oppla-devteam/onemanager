<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servizio per fatturazione automatica Partner OPPLA
 * Include: fee consegne (base + km), abbonamenti, POS, servizi extra, upselling
 */
class PartnerInvoicingService
{
    protected $ficService;

    public function __construct(FattureInCloudService $ficService)
    {
        $this->ficService = $ficService;
    }

    /**
     * Genera fatture mensili per tutti i Partner OPPLA attivi
     */
    public function generateAllPartnerInvoices(int $month, int $year): array
    {
        $partners = Client::where('type', 'partner_oppla')
            ->where('status', 'active')
            ->get();

        $invoices = [];

        foreach ($partners as $partner) {
            try {
                $invoice = $this->generatePartnerMonthlyInvoice($partner, $month, $year);
                if ($invoice) {
                    $invoices[] = $invoice;
                }
            } catch (\Exception $e) {
                Log::error("Errore fatturazione partner {$partner->id}: " . $e->getMessage());
            }
        }

        return $invoices;
    }

    /**
     * Genera fattura mensile completa per Partner OPPLA con tutte le fee
     */
    public function generatePartnerMonthlyInvoice(Client $client, int $month, int $year): ?Invoice
    {
        DB::beginTransaction();

        try {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $items = [];
            $totalImponibile = 0;

            // 1. Fee Consegne (base + km)
            $deliveryFee = $this->calculateDeliveryFees($client, $startDate, $endDate);
            if ($deliveryFee['total'] > 0) {
                $items[] = [
                    'description' => "Fee Consegne - {$deliveryFee['count']} consegne (Base + {$deliveryFee['km']}km)",
                    'quantity' => 1,
                    'unit_price' => $deliveryFee['total'],
                    'vat_rate' => 22,
                ];
                $totalImponibile += $deliveryFee['total'];
            }

            // 2. Ordini Cash (fee per ordine)
            $cashOrders = $this->calculateCashOrdersFee($client, $startDate, $endDate);
            if ($cashOrders['total'] > 0) {
                $items[] = [
                    'description' => "Fee Ordini Cash - {$cashOrders['count']} ordini",
                    'quantity' => $cashOrders['count'],
                    'unit_price' => $cashOrders['fee_per_order'],
                    'vat_rate' => 22,
                ];
                $totalImponibile += $cashOrders['total'];
            }

            // 3. Fee Ordini POS
            $posOrders = $this->calculatePosOrdersFee($client, $startDate, $endDate);
            if ($posOrders['total'] > 0) {
                $items[] = [
                    'description' => "Fee Ordini POS - {$posOrders['count']} ordini",
                    'quantity' => $posOrders['count'],
                    'unit_price' => $posOrders['fee_per_order'],
                    'vat_rate' => 22,
                ];
                $totalImponibile += $posOrders['total'];
            }

            // 4. Abbonamento Mensile
            if ($client->abbonamento_mensile > 0) {
                $items[] = [
                    'description' => "Abbonamento Mensile OPPLA",
                    'quantity' => 1,
                    'unit_price' => $client->abbonamento_mensile,
                    'vat_rate' => 22,
                ];
                $totalImponibile += $client->abbonamento_mensile;
            }

            // 5. Fee POS (se attivo)
            if ($client->has_pos && $client->fee_mensile > 0) {
                $items[] = [
                    'description' => "Canone Mensile POS",
                    'quantity' => 1,
                    'unit_price' => $client->fee_mensile,
                    'vat_rate' => 22,
                ];
                $totalImponibile += $client->fee_mensile;
            }

            // 6. Servizi Extra Attivi
            $services = $this->getActiveServices($client, $startDate, $endDate);
            foreach ($services as $service) {
                $items[] = [
                    'description' => $service['description'],
                    'quantity' => 1,
                    'unit_price' => $service['price'],
                    'vat_rate' => 22,
                ];
                $totalImponibile += $service['price'];
            }

            // 7. Upselling (vendite extra: zaini, packaging, etc.)
            $upselling = $this->getUpsellingSales($client, $startDate, $endDate);
            foreach ($upselling as $sale) {
                $items[] = [
                    'description' => $sale['description'],
                    'quantity' => $sale['quantity'],
                    'unit_price' => $sale['unit_price'],
                    'vat_rate' => 22,
                ];
                $totalImponibile += $sale['quantity'] * $sale['unit_price'];
            }

            // 8. Storno Coupon (se presenti)
            $coupons = $this->getCouponDeductions($client, $startDate, $endDate);
            if ($coupons['total'] > 0) {
                $items[] = [
                    'description' => "Storno Coupon - {$coupons['count']} coupon applicati",
                    'quantity' => 1,
                    'unit_price' => -$coupons['total'], // Negativo per storno
                    'vat_rate' => 22,
                ];
                $totalImponibile -= $coupons['total'];
            }

            // Se non ci sono items, non creare fattura
            if (empty($items) || $totalImponibile <= 0) {
                DB::rollBack();
                Log::info("Nessuna fattura da generare per Partner {$client->id} - totale €0");
                return null;
            }

            $totalIva = $totalImponibile * 0.22;
            $totalAmount = $totalImponibile + $totalIva;

            // Crea fattura
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'type' => 'attiva',
                'invoice_type' => 'partner_mensile',
                'data_emissione' => $endDate->copy()->addDay(), // 1° del mese successivo
                'data_scadenza' => $endDate->copy()->addDays(30),
                'imponibile' => $totalImponibile,
                'iva' => $totalIva,
                'totale' => $totalAmount,
                'status' => 'issued',
                'payment_status' => 'non_pagata',
                'payment_method' => 'bank_transfer',
                'causale' => "Fattura mensile {$startDate->format('m/Y')} - Partner OPPLA",
            ]);

            $invoice->generateInvoiceNumber();

            // Crea items fattura
            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'],
                    'total' => $item['quantity'] * $item['unit_price'] * (1 + $item['vat_rate'] / 100),
                ]);
            }

            // Sincronizza con Fatture in Cloud
            $this->syncInvoiceToFIC($invoice);

            DB::commit();

            Log::info('Fattura Partner OPPLA creata', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->numero_fattura,
                'client_id' => $client->id,
                'client_name' => $client->ragione_sociale,
                'total' => $totalAmount,
                'items_count' => count($items),
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione fattura Partner OPPLA: ' . $e->getMessage(), [
                'client_id' => $client->id,
                'month' => $month,
                'year' => $year,
            ]);
            throw $e;
        }
    }

    /**
     * Calcola fee consegne (base + km)
     */
    private function calculateDeliveryFees(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $deliveries = $client->deliveries()
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->get();

        $totalFee = 0;
        $totalKm = 0;

        foreach ($deliveries as $delivery) {
            // Fee base per consegna
            $totalFee += $client->fee_consegna_base ?? 0;

            // Fee per km
            if ($delivery->distance_km && $client->fee_consegna_km) {
                $kmFee = $delivery->distance_km * $client->fee_consegna_km;
                $totalFee += $kmFee;
                $totalKm += $delivery->distance_km;
            }
        }

        return [
            'count' => $deliveries->count(),
            'km' => round($totalKm, 2),
            'total' => round($totalFee, 2),
        ];
    }

    /**
     * Calcola fee ordini cash
     */
    private function calculateCashOrdersFee(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $cashOrders = $client->deliveries()
            ->where('payment_method', 'cash')
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->get();

        $feePerOrder = $client->fee_ordine ?? 0;

        return [
            'count' => $cashOrders->count(),
            'fee_per_order' => $feePerOrder,
            'total' => $cashOrders->count() * $feePerOrder,
        ];
    }

    /**
     * Calcola fee ordini POS
     */
    private function calculatePosOrdersFee(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $posOrders = $client->posOrders()
            ->whereBetween('order_date', [$startDate, $endDate])
            ->get();

        $feePerOrder = $client->fee_ordine ?? 0;

        return [
            'count' => $posOrders->count(),
            'fee_per_order' => $feePerOrder,
            'total' => $posOrders->count() * $feePerOrder,
        ];
    }

    /**
     * Ottiene servizi extra attivi
     */
    private function getActiveServices(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $services = $client->clientServices()
            ->where('is_active', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where(function ($sq) use ($startDate) {
                              $sq->whereNull('end_date')
                                 ->orWhere('end_date', '>=', $startDate);
                          });
                    });
            })
            ->with('service')
            ->get();

        return $services->map(function ($clientService) {
            return [
                'description' => $clientService->service->name ?? 'Servizio Extra',
                'price' => $clientService->price,
            ];
        })->toArray();
    }

    /**
     * Ottiene vendite upselling
     */
    private function getUpsellingSales(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        $sales = $client->upsellingSales()
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->get();

        return $sales->map(function ($sale) {
            return [
                'description' => $sale->description,
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
            ];
        })->toArray();
    }

    /**
     * Calcola coupon da stornare
     */
    private function getCouponDeductions(Client $client, Carbon $startDate, Carbon $endDate): array
    {
        // Qui dovresti avere una tabella coupons applicati
        // Per ora ritorno un array vuoto, da implementare quando hai la tabella
        return [
            'count' => 0,
            'total' => 0,
        ];
    }

    /**
     * Sincronizza fattura con Fatture in Cloud
     */
    private function syncInvoiceToFIC(Invoice $invoice): void
    {
        $client = $invoice->client;

        // Sincronizza cliente
        $ficClientData = [
            'name' => $client->ragione_sociale,
            'vat_number' => $client->piva,
            'tax_code' => $client->codice_fiscale,
            'address_street' => $client->indirizzo,
            'address_city' => $client->citta,
            'address_province' => $client->provincia,
            'address_postal_code' => $client->cap,
            'country' => $client->nazione ?? 'Italia',
            'email' => $client->email,
            'certified_email' => $client->pec,
            'phone' => $client->phone,
            'e_invoice' => true,
            'ei_code' => $client->sdi_code,
        ];

        try {
            $connection = \App\Models\FattureInCloudConnection::where('user_id', 1)
                ->where('is_active', true)
                ->first();

            if (!$connection) {
                Log::warning('Nessuna connessione FIC attiva, skip sync');
                return;
            }

            // Crea/Aggiorna cliente su FIC
            $ficClient = $this->ficService->createOrUpdateClient($connection, $ficClientData);

            // Prepara dati fattura
            $ficInvoiceData = [
                'type' => 'invoice',
                'entity' => [
                    'id' => $ficClient['id'] ?? null,
                    'name' => $client->ragione_sociale,
                    'vat_number' => $client->piva,
                    'tax_code' => $client->codice_fiscale,
                ],
                'date' => $invoice->data_emissione->format('Y-m-d'),
                'number' => $invoice->numero_progressivo,
                'numeration' => '/FE',
                'items_list' => $invoice->items->map(function ($item) {
                    return [
                        'product_id' => null,
                        'code' => null,
                        'name' => $item->description,
                        'measure' => '',
                        'net_price' => (float) $item->unit_price,
                        'category' => '',
                        'qty' => (float) $item->quantity,
                        'vat' => [
                            'id' => null,
                            'value' => (float) $item->vat_rate,
                            'description' => '',
                        ],
                        'not_taxable' => false,
                    ];
                })->toArray(),
                'payments_list' => [
                    [
                        'amount' => (float) $invoice->totale,
                        'due_date' => $invoice->data_scadenza->format('Y-m-d'),
                        'paid_date' => null,
                        'payment_terms' => [
                            'days' => 30,
                            'type' => 'standard',
                        ],
                        'status' => 'not_paid',
                    ],
                ],
                'payment_method' => [
                    'name' => 'Bonifico bancario',
                ],
            ];

            // Crea fattura su FIC
            $ficInvoice = $this->ficService->createIssuedInvoice($connection, $ficInvoiceData);

            // Salva ID FIC
            $invoice->fic_document_id = $ficInvoice['id'] ?? null;
            $invoice->save();

            // Invia automaticamente a SDI se configurato
            if (config('fatture_in_cloud.auto_send_sdi', true) && $invoice->fic_document_id) {
                $this->ficService->sendInvoiceToSDI($connection, $invoice->fic_document_id);
                $invoice->sdi_status = 'sent';
                $invoice->sdi_sent_at = now();
                $invoice->save();
            }

            Log::info('Fattura sincronizzata con FIC', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Errore sync FIC: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
            ]);
            // Non bloccare la creazione della fattura locale
        }
    }
}
