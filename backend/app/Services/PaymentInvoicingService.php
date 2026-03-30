<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentInvoicingService
{
    /**
     * Preview fattura da pagamenti aggregati
     */
    public function previewInvoiceFromPayments(Client $client, array $paymentIds, array $filters = []): array
    {
        $payments = BankTransaction::whereIn('id', $paymentIds)
            ->where('client_id', $client->id)
            ->where('amount', '>', 0) // Solo entrate
            ->orderBy('transaction_date')
            ->get();

        if ($payments->isEmpty()) {
            throw new \Exception('Nessun pagamento valido trovato');
        }

        // Raggruppa per descrizione simile
        $groupedPayments = $this->groupPaymentsByDescription($payments);

        $items = [];
        $totalImponibile = 0;
        $totalIva = 0;

        foreach ($groupedPayments as $group) {
            $quantity = count($group['payments']);
            $totalAmount = $group['total'];
            $unitPrice = $totalAmount / $quantity;
            
            // Calcola IVA (22% default)
            $ivaRate = 22;
            $imponibile = round($totalAmount / (1 + ($ivaRate / 100)), 2);
            $iva = $totalAmount - $imponibile;

            $items[] = [
                'descrizione' => $group['description'],
                'quantita' => $quantity,
                'prezzo_unitario' => round($unitPrice, 2),
                'imponibile' => round($imponibile, 2),
                'iva_percentuale' => $ivaRate,
                'iva' => round($iva, 2),
                'totale' => $totalAmount,
                'payment_ids' => array_column($group['payments'], 'id'),
            ];

            $totalImponibile += $imponibile;
            $totalIva += $iva;
        }

        $totalAmount = $totalImponibile + $totalIva;

        return [
            'client' => [
                'id' => $client->id,
                'ragione_sociale' => $client->ragione_sociale,
                'piva' => $client->piva,
                'codice_fiscale' => $client->codice_fiscale,
                'email' => $client->email,
                'pec' => $client->pec,
                'sdi_code' => $client->sdi_code,
                'indirizzo' => $client->indirizzo,
                'citta' => $client->citta,
                'provincia' => $client->provincia,
                'cap' => $client->cap,
            ],
            'items' => $items,
            'payments' => $payments->map(fn($p) => [
                'id' => $p->id,
                'transaction_date' => $p->transaction_date->format('d/m/Y'),
                'descrizione' => $p->descrizione,
                'amount' => $p->amount,
                'source' => $p->source,
            ])->toArray(),
            'totals' => [
                'imponibile' => round($totalImponibile, 2),
                'iva' => round($totalIva, 2),
                'totale' => round($totalAmount, 2),
            ],
            'suggested_data' => [
                'data_emissione' => now()->format('Y-m-d'),
                'data_scadenza' => now()->addDays(30)->format('Y-m-d'),
                'payment_method' => 'bonifico',
            ],
        ];
    }

    /**
     * Genera fattura da pagamenti
     */
    public function generateInvoiceFromPayments(
        Client $client,
        array $paymentIds,
        array $invoiceData,
        array $items
    ): Invoice {
        DB::beginTransaction();

        try {
            // Verifica che i pagamenti non siano già fatturati
            $alreadyInvoiced = BankTransaction::whereIn('id', $paymentIds)
                ->where('is_reconciled', true)
                ->whereNotNull('invoice_id')
                ->count();

            if ($alreadyInvoiced > 0) {
                throw new \Exception("Alcuni pagamenti sono già stati fatturati");
            }

            // Calcola totali
            $totalImponibile = 0;
            $totalIva = 0;
            foreach ($items as $item) {
                $totalImponibile += $item['imponibile'];
                $totalIva += $item['iva'];
            }
            $totalAmount = $totalImponibile + $totalIva;

            // Crea fattura (numero generato dopo con lock)
            $invoice = new Invoice([
                'client_id' => $client->id,
                'type' => 'attiva',
                'invoice_type' => 'ordinaria',
                'anno' => now()->year,
                'data_emissione' => $invoiceData['data_emissione'],
                'data_scadenza' => $invoiceData['data_scadenza'] ?? now()->addDays(30),
                'imponibile' => $totalImponibile,
                'iva' => $totalIva,
                'totale' => $totalAmount,
                'status' => 'emessa',
                'payment_status' => 'non_pagata',
                'payment_method' => $invoiceData['payment_method'] ?? 'bonifico',
                'note' => $invoiceData['note'] ?? 'Fattura generata da pagamenti aggregati',
            ]);
            
            // Genera numero fattura DENTRO transazione con lock FOR UPDATE
            $invoice->generateInvoiceNumber();
            $invoice->save();

            // Crea righe fattura
            foreach ($items as $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'descrizione' => $itemData['descrizione'],
                    'quantita' => $itemData['quantita'],
                    'prezzo_unitario' => $itemData['prezzo_unitario'],
                    'sconto' => 0,
                    'iva_percentuale' => $itemData['iva_percentuale'],
                    'subtotale' => $itemData['imponibile'],
                ]);
            }

            // Riconcilia pagamenti con fattura
            BankTransaction::whereIn('id', $paymentIds)->update([
                'is_reconciled' => true,
                'invoice_id' => $invoice->id,
            ]);

            DB::commit();

            Log::info('[PaymentInvoicing] Fattura generata da pagamenti', [
                'invoice_id' => $invoice->id,
                'client_id' => $client->id,
                'payment_count' => count($paymentIds),
                'total' => $totalAmount,
            ]);

            return $invoice->load('items', 'client');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PaymentInvoicing] Errore generazione fattura: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Raggruppa pagamenti per descrizione simile
     */
    private function groupPaymentsByDescription($payments): array
    {
        $groups = [];
        
        foreach ($payments as $payment) {
            $description = $this->normalizeDescription($payment->descrizione);
            
            // Cerca gruppo esistente
            $found = false;
            foreach ($groups as &$group) {
                if ($this->isSimilarDescription($description, $group['description'])) {
                    $group['payments'][] = [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'date' => $payment->transaction_date,
                    ];
                    $group['total'] += $payment->amount;
                    $found = true;
                    break;
                }
            }
            
            // Crea nuovo gruppo
            if (!$found) {
                $groups[] = [
                    'description' => $description,
                    'payments' => [[
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'date' => $payment->transaction_date,
                    ]],
                    'total' => $payment->amount,
                ];
            }
        }

        return $groups;
    }

    /**
     * Normalizza descrizione pagamento
     */
    private function normalizeDescription(string $description): string
    {
        // Rimuovi date, numeri di transazione, etc.
        $description = preg_replace('/\d{2}\/\d{2}\/\d{4}/', '', $description);
        $description = preg_replace('/\b\d{10,}\b/', '', $description);
        $description = preg_replace('/[A-Z0-9]{10,}/', '', $description);
        
        // Pulisci e trim
        $description = trim(preg_replace('/\s+/', ' ', $description));
        
        // Se troppo generico, usa "Servizio"
        if (strlen($description) < 5) {
            return 'Servizio OPPLA';
        }
        
        return $description;
    }

    /**
     * Verifica se due descrizioni sono simili
     */
    private function isSimilarDescription(string $desc1, string $desc2): bool
    {
        $desc1 = strtolower($desc1);
        $desc2 = strtolower($desc2);
        
        // Exact match
        if ($desc1 === $desc2) {
            return true;
        }
        
        // Contains match (almeno 80% in comune)
        $len1 = strlen($desc1);
        $len2 = strlen($desc2);
        $minLen = min($len1, $len2);
        
        if ($minLen < 5) {
            return false;
        }
        
        similar_text($desc1, $desc2, $percent);
        
        return $percent >= 80;
    }
}
