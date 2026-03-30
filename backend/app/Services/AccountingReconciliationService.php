<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\SupplierInvoice;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\AccountingCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingReconciliationService
{
    /**
     * Riconcilia transazioni bancarie con fatture
     */
    public function reconcileTransactions(array $transactionIds = []): array
    {
        $results = [
            'reconciled' => 0,
            'failed' => 0,
            'partial' => 0,
            'details' => [],
        ];

        $query = BankTransaction::unreconciled();
        
        if (!empty($transactionIds)) {
            $query->whereIn('id', $transactionIds);
        }

        $transactions = $query->get();

        foreach ($transactions as $transaction) {
            $reconciled = $this->reconcileTransaction($transaction);
            
            if ($reconciled === true) {
                $results['reconciled']++;
                $results['details'][] = [
                    'transaction_id' => $transaction->id,
                    'status' => 'reconciled',
                    'matched_to' => $transaction->invoice_id ?? $transaction->supplier_invoice_id,
                ];
            } elseif ($reconciled === 'partial') {
                $results['partial']++;
                $results['details'][] = [
                    'transaction_id' => $transaction->id,
                    'status' => 'categorized',
                    'category' => $transaction->category?->name,
                ];
            } else {
                $results['failed']++;
            }
        }

        Log::info('[Riconciliazione] Completata', $results);
        return $results;
    }

    /**
     * Auto-riconciliazione intelligente con scoring
     */
    public function autoReconcileWithScoring(): array
    {
        $results = [
            'high_confidence' => 0,
            'medium_confidence' => 0,
            'low_confidence' => 0,
            'unmatched' => 0,
            'matches' => [],
        ];

        $unreconciledTransactions = BankTransaction::unreconciled()->get();

        foreach ($unreconciledTransactions as $transaction) {
            $matches = $this->findPotentialMatches($transaction);

            if (empty($matches)) {
                $results['unmatched']++;
                continue;
            }

            // Ordina per score decrescente
            usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
            $bestMatch = $matches[0];

            if ($bestMatch['score'] >= 90) {
                // Alta confidenza - riconcilia automaticamente
                $this->applyReconciliation($transaction, $bestMatch);
                $results['high_confidence']++;
            } elseif ($bestMatch['score'] >= 70) {
                // Media confidenza - segna come suggerimento
                $results['medium_confidence']++;
            } else {
                // Bassa confidenza - richiede revisione manuale
                $results['low_confidence']++;
            }

            $results['matches'][] = [
                'transaction_id' => $transaction->id,
                'transaction_desc' => $transaction->descrizione,
                'amount' => $transaction->amount,
                'best_match' => $bestMatch,
                'all_matches' => array_slice($matches, 0, 3),
            ];
        }

        return $results;
    }

    /**
     * Trova potenziali corrispondenze con scoring
     */
    private function findPotentialMatches(BankTransaction $transaction): array
    {
        $matches = [];

        if ($transaction->type === 'entrata' || $transaction->amount > 0) {
            // Cerca tra fatture clienti
            $invoices = $this->findCandidateInvoices($transaction);
            foreach ($invoices as $invoice) {
                $score = $this->calculateMatchScore($transaction, $invoice, 'invoice');
                if ($score > 50) {
                    $matches[] = [
                        'type' => 'invoice',
                        'id' => $invoice->id,
                        'score' => $score,
                        'client' => $invoice->client?->ragione_sociale,
                        'invoice_number' => $invoice->numero_fattura,
                        'invoice_amount' => $invoice->totale,
                        'reasons' => $this->getMatchReasons($transaction, $invoice, 'invoice'),
                    ];
                }
            }
        }

        if ($transaction->type === 'uscita' || $transaction->amount < 0) {
            // Cerca tra fatture fornitori
            $supplierInvoices = $this->findCandidateSupplierInvoices($transaction);
            foreach ($supplierInvoices as $supplierInvoice) {
                $score = $this->calculateMatchScore($transaction, $supplierInvoice, 'supplier_invoice');
                if ($score > 50) {
                    $matches[] = [
                        'type' => 'supplier_invoice',
                        'id' => $supplierInvoice->id,
                        'score' => $score,
                        'supplier' => $supplierInvoice->supplier?->ragione_sociale,
                        'invoice_number' => $supplierInvoice->numero_fattura,
                        'invoice_amount' => $supplierInvoice->totale,
                        'reasons' => $this->getMatchReasons($transaction, $supplierInvoice, 'supplier_invoice'),
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Calcola score di corrispondenza (0-100)
     */
    private function calculateMatchScore(BankTransaction $transaction, $document, string $type): int
    {
        $score = 0;
        $amount = abs($transaction->amount);
        $documentAmount = $type === 'invoice' ? $document->totale : $document->totale;

        // Match importo esatto: +40 punti
        if (abs($amount - $documentAmount) < 0.01) {
            $score += 40;
        }
        // Match importo con tolleranza 1%: +30 punti
        elseif (abs($amount - $documentAmount) / $documentAmount < 0.01) {
            $score += 30;
        }
        // Match importo con tolleranza 5%: +15 punti
        elseif (abs($amount - $documentAmount) / $documentAmount < 0.05) {
            $score += 15;
        }

        // Match beneficiario/ragione sociale: +30 punti
        $entityName = $type === 'invoice' 
            ? ($document->client?->ragione_sociale ?? '')
            : ($document->supplier?->ragione_sociale ?? '');
        
        if ($this->fuzzyMatchBeneficiary($transaction->beneficiario, $entityName)) {
            $score += 30;
        }

        // Match data (±5 giorni): +20 punti
        $transactionDate = $transaction->transaction_date;
        $documentDate = $type === 'invoice' ? $document->data_emissione : $document->data_emissione;
        
        if ($documentDate && abs($transactionDate->diffInDays($documentDate)) <= 5) {
            $score += 20;
        } elseif ($documentDate && abs($transactionDate->diffInDays($documentDate)) <= 15) {
            $score += 10;
        }

        // Bonus per riferimenti in descrizione: +10 punti
        $description = strtolower($transaction->descrizione ?? '');
        $invoiceNumber = strtolower($document->numero_fattura ?? '');
        
        if ($invoiceNumber && str_contains($description, $invoiceNumber)) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Fuzzy matching per nomi beneficiari
     */
    private function fuzzyMatchBeneficiary(?string $beneficiary, ?string $entityName): bool
    {
        if (empty($beneficiary) || empty($entityName)) {
            return false;
        }

        $beneficiary = $this->normalizeCompanyName($beneficiary);
        $entityName = $this->normalizeCompanyName($entityName);

        // Match esatto
        if ($beneficiary === $entityName) {
            return true;
        }

        // Match parziale (uno contiene l'altro)
        if (str_contains($beneficiary, $entityName) || str_contains($entityName, $beneficiary)) {
            return true;
        }

        // Levenshtein distance per typos
        $distance = levenshtein($beneficiary, $entityName);
        $maxLength = max(strlen($beneficiary), strlen($entityName));
        
        if ($maxLength > 0 && ($distance / $maxLength) < 0.3) {
            return true;
        }

        // Similar text percentage
        similar_text($beneficiary, $entityName, $percent);
        if ($percent > 70) {
            return true;
        }

        return false;
    }

    /**
     * Normalizza nome azienda (rimuove forme societarie, punteggiatura, etc.)
     */
    private function normalizeCompanyName(?string $name): string
    {
        if (empty($name)) {
            return '';
        }

        $name = strtolower(trim($name));
        
        // Rimuovi forme societarie italiane
        $patterns = [
            '/\b(s\.?r\.?l\.?s?|s\.?p\.?a\.?|s\.?a\.?s\.?|s\.?n\.?c\.?)\b/i',
            '/\b(societa|società|ditta|azienda)\b/i',
            '/\b(unipersonale|individuale)\b/i',
            '/[\.\-\_\'\"\,\;\:]/i',
        ];
        
        foreach ($patterns as $pattern) {
            $name = preg_replace($pattern, ' ', $name);
        }

        // Rimuovi spazi multipli
        $name = preg_replace('/\s+/', ' ', $name);
        
        return trim($name);
    }

    /**
     * Trova fatture candidate per matching
     */
    private function findCandidateInvoices(BankTransaction $transaction): Collection
    {
        $amount = abs($transaction->amount);
        $tolerance = $amount * 0.05; // 5% tolerance

        return Invoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('totale', [$amount - $tolerance, $amount + $tolerance])
            ->with('client:id,ragione_sociale')
            ->limit(10)
            ->get();
    }

    /**
     * Trova fatture fornitore candidate per matching
     */
    private function findCandidateSupplierInvoices(BankTransaction $transaction): Collection
    {
        $amount = abs($transaction->amount);
        $tolerance = $amount * 0.05;

        return SupplierInvoice::where('payment_status', '!=', 'pagata')
            ->whereBetween('totale', [$amount - $tolerance, $amount + $tolerance])
            ->with('supplier:id,ragione_sociale')
            ->limit(10)
            ->get();
    }

    /**
     * Restituisce i motivi del match per trasparenza
     */
    private function getMatchReasons(BankTransaction $transaction, $document, string $type): array
    {
        $reasons = [];
        $amount = abs($transaction->amount);
        $documentAmount = $document->totale;

        if (abs($amount - $documentAmount) < 0.01) {
            $reasons[] = 'Importo esatto';
        } elseif (abs($amount - $documentAmount) / $documentAmount < 0.05) {
            $reasons[] = 'Importo simile (±5%)';
        }

        $entityName = $type === 'invoice' 
            ? ($document->client?->ragione_sociale ?? '')
            : ($document->supplier?->ragione_sociale ?? '');
        
        if ($this->fuzzyMatchBeneficiary($transaction->beneficiario, $entityName)) {
            $reasons[] = 'Beneficiario corrisponde';
        }

        return $reasons;
    }

    /**
     * Applica la riconciliazione
     */
    private function applyReconciliation(BankTransaction $transaction, array $match): void
    {
        DB::transaction(function() use ($transaction, $match) {
            if ($match['type'] === 'invoice') {
                $transaction->invoice_id = $match['id'];
                $invoice = Invoice::find($match['id']);
                if ($invoice) {
                    $invoice->update([
                        'payment_status' => 'pagata',
                        'data_pagamento' => $transaction->transaction_date,
                    ]);
                }
            } else {
                $transaction->supplier_invoice_id = $match['id'];
                $supplierInvoice = SupplierInvoice::find($match['id']);
                if ($supplierInvoice) {
                    $supplierInvoice->update([
                        'payment_status' => 'pagata',
                        'data_pagamento' => $transaction->transaction_date,
                    ]);
                }
            }

            $transaction->is_reconciled = true;
            $transaction->reconciled_at = now();
            $transaction->reconciliation_score = $match['score'];
            $transaction->save();

            Log::info('[Riconciliazione] Match applicato', [
                'transaction_id' => $transaction->id,
                'match_type' => $match['type'],
                'match_id' => $match['id'],
                'score' => $match['score'],
            ]);
        });
    }

    /**
     * Riconcilia una singola transazione
     */
    private function reconcileTransaction(BankTransaction $transaction): bool|string
    {
        // Se già riconciliata, skip
        if ($transaction->is_reconciled) {
            return true;
        }

        // Cerca corrispondenza con fatture attive (clienti)
        if ($transaction->type === 'entrata') {
            $invoice = $this->findMatchingInvoice($transaction);
            
            if ($invoice) {
                $transaction->invoice_id = $invoice->id;
                $transaction->is_reconciled = true;
                $transaction->save();

                // Aggiorna stato pagamento fattura
                $invoice->payment_status = 'paid';
                $invoice->data_pagamento = $transaction->transaction_date;
                $invoice->save();

                return true;
            }
        }

        // Cerca corrispondenza con fatture passive (fornitori)
        if ($transaction->type === 'uscita') {
            $supplierInvoice = $this->findMatchingSupplierInvoice($transaction);
            
            if ($supplierInvoice) {
                $transaction->supplier_invoice_id = $supplierInvoice->id;
                $transaction->is_reconciled = true;
                $transaction->save();

                // Aggiorna stato pagamento fattura fornitore
                $supplierInvoice->payment_status = 'paid';
                $supplierInvoice->paid_at = $transaction->transaction_date;
                $supplierInvoice->save();

                return true;
            }
        }

        // Se non trova match perfetto, prova riconciliazione parziale
        return $this->partialReconcile($transaction);
    }

    /**
     * Cerca fattura cliente corrispondente
     */
    private function findMatchingInvoice(BankTransaction $transaction): ?Invoice
    {
        // Cerca per Stripe transaction ID
        if (str_contains(strtolower($transaction->descrizione), 'stripe')) {
            if (preg_match('/[A-Z0-9]{20,}/', $transaction->descrizione, $matches)) {
                $invoice = Invoice::where('stripe_transaction_id', $matches[0])->first();
                if ($invoice) {
                    return $invoice;
                }
            }
        }

        // Cerca per importo e data vicina (±5 giorni)
        $startDate = $transaction->transaction_date->copy()->subDays(5);
        $endDate = $transaction->transaction_date->copy()->addDays(5);

        return Invoice::where('payment_status', '!=', 'paid')
            ->whereBetween('data_emissione', [$startDate, $endDate])
            ->where(function($query) use ($transaction) {
                $query->where('totale', $transaction->amount)
                    ->orWhere('totale_netto', $transaction->amount);
            })
            ->first();
    }

    /**
     * Cerca fattura fornitore corrispondente
     */
    private function findMatchingSupplierInvoice(BankTransaction $transaction): ?SupplierInvoice
    {
        // Cerca per beneficiario e importo
        if ($transaction->beneficiario) {
            $supplier = DB::table('suppliers')
                ->where('name', 'like', '%' . $transaction->beneficiario . '%')
                ->first();

            if ($supplier) {
                $startDate = $transaction->transaction_date->copy()->subDays(5);
                $endDate = $transaction->transaction_date->copy()->addDays(5);

                return SupplierInvoice::where('supplier_id', $supplier->id)
                    ->where('payment_status', '!=', 'paid')
                    ->whereBetween('invoice_date', [$startDate, $endDate])
                    ->where('total_amount', $transaction->amount)
                    ->first();
            }
        }

        // Cerca solo per importo e data vicina
        $startDate = $transaction->transaction_date->copy()->subDays(5);
        $endDate = $transaction->transaction_date->copy()->addDays(5);

        return SupplierInvoice::where('payment_status', '!=', 'paid')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('total_amount', $transaction->amount)
            ->first();
    }

    /**
     * Riconciliazione parziale (categorizzazione senza match fattura)
     */
    private function partialReconcile(BankTransaction $transaction): string
    {
        // Auto-categorizza basandosi su pattern noti
        $category = $this->detectCategory($transaction);
        
        if ($category) {
            $transaction->category_id = $category->id;
            $transaction->save();
            return 'partial';
        }

        return false;
    }

    /**
     * Rileva categoria automaticamente
     */
    private function detectCategory(BankTransaction $transaction): ?AccountingCategory
    {
        $description = strtolower($transaction->descrizione);

        // Pattern comuni
        $patterns = [
            'stipendi' => ['freschi', 'giachetti', 'moschella', 'superti', 'stipendio', 'compenso'],
            'commissioni_bancarie' => ['comm.', 'spese', 'commissione', 'imposta di bollo'],
            'stripe' => ['stripe', 'payment gateway'],
            'hosting' => ['siteground', 'render', 'aws'],
            'software' => ['canva', 'manychat', 'openai', 'postmark'],
            'professionisti' => ['avv', 'avvocato', 'commercialista', 'notaio', 'pardini', 'scapuzzi'],
            'ufficio' => ['affitto', 'condominio', 'fastweb', 'enel', 'utenze'],
        ];

        foreach ($patterns as $categorySlug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($description, $keyword)) {
                    return AccountingCategory::where('slug', $categorySlug)->first();
                }
            }
        }

        return null;
    }

    /**
     * Genera report riconciliazione
     */
    public function generateReconciliationReport(int $month, int $year): array
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

        $transactions = BankTransaction::inPeriod($startDate, $endDate)->get();

        $totalTransactions = $transactions->count();
        $reconciledTransactions = $transactions->where('is_reconciled', true)->count();
        $unreconciledTransactions = $totalTransactions - $reconciledTransactions;

        $totalEntrate = $transactions->where('type', 'entrata')->sum('amount');
        $totalUscite = $transactions->where('type', 'uscita')->sum('amount');

        $reconciledEntrate = $transactions->where('type', 'entrata')
            ->where('is_reconciled', true)->sum('amount');
        $reconciledUscite = $transactions->where('type', 'uscita')
            ->where('is_reconciled', true)->sum('amount');

        // Raggruppa per categoria
        $byCategory = $transactions->groupBy('category_id')
            ->map(function($items) {
                return [
                    'count' => $items->count(),
                    'total' => $items->sum('amount'),
                    'category' => $items->first()?->category?->name ?? 'Non categorizzato',
                ];
            });

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'reconciled' => $reconciledTransactions,
                'unreconciled' => $unreconciledTransactions,
                'reconciliation_rate' => $totalTransactions > 0 
                    ? round(($reconciledTransactions / $totalTransactions) * 100, 2) 
                    : 0,
            ],
            'amounts' => [
                'total_entrate' => round($totalEntrate, 2),
                'total_uscite' => round($totalUscite, 2),
                'balance' => round($totalEntrate - $totalUscite, 2),
                'reconciled_entrate' => round($reconciledEntrate, 2),
                'reconciled_uscite' => round($reconciledUscite, 2),
            ],
            'by_category' => $byCategory,
            'unreconciled_transactions' => $transactions->where('is_reconciled', false)
                ->map(function($t) {
                    return [
                        'id' => $t->id,
                        'date' => $t->transaction_date->toDateString(),
                        'type' => $t->type,
                        'amount' => $t->amount,
                        'description' => $t->descrizione,
                        'beneficiary' => $t->beneficiario,
                    ];
                })->values(),
        ];
    }
}
