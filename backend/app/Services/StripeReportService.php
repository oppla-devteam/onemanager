<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StripeReportService
{
    /**
     * Ottieni tutte le transazioni Stripe per un dato mese
     */
    public function getMonthlyTransactions(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Recupera transazioni da bank_transactions con source = 'stripe'
        $transactions = DB::table('bank_transactions')
            ->where('source', 'stripe')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'asc')
            ->get()
            ->map(function ($transaction) {
                // Decodifica source_data per ottenere informazioni aggiuntive
                $sourceData = json_decode($transaction->source_data ?? '{}', true);
                $note = json_decode($transaction->note ?? '{}', true);
                
                // Determina il tipo Stripe dalla source_data o note
                $stripeType = $this->determineStripeType($transaction, $sourceData, $note);
                
                return (object) [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->source_transaction_id,
                    'type' => strtolower($stripeType), // NORMALIZZA: sempre minuscolo
                    'source' => $sourceData['stripe_source'] ?? $transaction->source_transaction_id,
                    'amount' => (float) $transaction->amount,
                    'fee' => (float) ($transaction->fee ?? 0),
                    'net' => (float) ($transaction->net_amount ?? $transaction->amount),
                    'created_at' => $transaction->transaction_date,
                    'description' => $transaction->descrizione,
                    'source_data' => $transaction->source_data, // Necessario per normalizzazione
                    'auto_corrected' => false,
                    'manually_corrected' => false,
                ];
            });

        // AGGIUNGI: Recupera application_fee dalla tabella dedicata (se esiste)
        $applicationFees = collect();
        try {
            if (DB::getSchemaBuilder()->hasTable('application_fees')) {
                $periodMonth = sprintf('%04d-%02d', $year, $month);
                $applicationFees = DB::table('application_fees')
                    ->where('period_month', $periodMonth)
                    ->orderBy('created_at_stripe', 'asc')
                    ->get()
                    ->map(function ($fee) {
                        return (object) [
                            'id' => 'fee_' . $fee->id,
                            'transaction_id' => $fee->stripe_fee_id,
                            'type' => 'application_fee',
                            'source' => $fee->stripe_fee_id,
                            'amount' => (float) $fee->amount,
                            'fee' => 0,
                            'net' => (float) $fee->amount,
                            'created_at' => $fee->created_at_stripe,
                            'description' => $fee->description ?? 'Commissione riscossa',
                            'source_data' => null,
                            'auto_corrected' => false,
                            'manually_corrected' => false,
                        ];
                    });
            }
        } catch (\Exception $e) {
            Log::warning('Tabella application_fees non disponibile: ' . $e->getMessage());
        }

        // Unisci transactions e applicationFees, ordina per data
        $allTransactions = $transactions
            ->concat($applicationFees)
            ->sortBy('created_at')
            ->values();

        return $allTransactions->toArray();
    }
    
    /**
     * Determina il tipo Stripe dalla transazione
     */
    private function determineStripeType($transaction, array $sourceData, array $note): string
    {
        // PRIORITÀ 1: Se il tipo è salvato in source_data (CORRETTO)
        if (isset($sourceData['stripe_type']) && !empty($sourceData['stripe_type'])) {
            return $sourceData['stripe_type'];
        }
        
        // PRIORITÀ 2: Se il tipo è salvato nelle note
        if (isset($note['stripe_type']) && !empty($note['stripe_type'])) {
            return $note['stripe_type'];
        }
        
        // PRIORITÀ 3: Deduzione SMART da transaction_id, source e descrizione
        $txnId = $transaction->source_transaction_id ?? '';
        $source = $sourceData['stripe_source'] ?? ''; // Il vero ID dell'oggetto (charge, transfer, etc.)
        $description = strtolower($transaction->descrizione ?? '');
        
        // PRIMA: Controlla il SOURCE (il vero ID dell'oggetto Stripe)
        // Transfers (tr_) - IMPORTANTE: per i balance transactions di tipo transfer, il source inizia con tr_
        if (str_starts_with($source, 'tr_')) {
            return 'transfer';
        }
        
        // Charges (ch_)
        if (str_starts_with($source, 'ch_')) {
            return 'charge';
        }
        
        // Payments (py_)
        if (str_starts_with($source, 'py_')) {
            return 'payment';
        }
        
        // Payouts (po_)
        if (str_starts_with($source, 'po_')) {
            return 'payout';
        }
        
        // Refunds (re_ or pyr_)
        if (str_starts_with($source, 're_') || str_starts_with($source, 'pyr_')) {
            return 'refund';
        }
        
        // Application fees (fee_)
        if (str_starts_with($source, 'fee_')) {
            return 'application_fee';
        }
        
        // DOPO: Se il source non identifica il tipo, controlla il transaction_id
        // Balance Transactions (txn_) - usa la descrizione per capire il tipo
        if (str_starts_with($txnId, 'txn_')) {
            // Stripe fees
            if (str_contains($description, 'stripe fee') || 
                str_contains($description, 'stripe volume') || 
                str_contains($description, 'stripe per-authorization') ||
                str_contains($description, 'radar')) {
                return 'stripe_fee';
            }
            
            // Network costs
            if (str_contains($description, 'network cost') || 
                str_contains($description, 'transaction network')) {
                return 'network_cost';
            }
            
            // Application fees
            if (str_contains($description, 'application fee')) {
                return 'application_fee';
            }
            
            // Taxes
            if (str_contains($description, 'automatic tax')) {
                return 'stripe_fee'; // Trattalo come fee
            }
            
            // Minimum balance (reserve)
            if (str_contains($description, 'minimum balance')) {
                return 'reserve';
            }
        }
        
        // Charges (ch_) - se il transaction_id stesso inizia così
        if (str_starts_with($txnId, 'ch_')) {
            return 'charge';
        }
        
        // Payments (py_)
        if (str_starts_with($txnId, 'py_')) {
            return 'payment';
        }
        
        // Payouts (po_)
        if (str_starts_with($txnId, 'po_')) {
            return 'payout';
        }
        
        // Refunds (re_ or pyr_)
        if (str_starts_with($txnId, 're_') || str_starts_with($txnId, 'pyr_')) {
            return 'refund';
        }
        
        // Transfers (tr_) - fallback se anche il txn_id inizia così
        if (str_starts_with($txnId, 'tr_')) {
            return 'transfer';
        }
        
        // Application fees (fee_)
        if (str_starts_with($txnId, 'fee_')) {
            return 'application_fee';
        }
        
        // PRIORITÀ 4: Analizza la causale come ultimo tentativo
        $causale = strtolower($transaction->causale ?? '');
        if (str_contains($causale, 'transfer')) {
            return 'transfer';
        }
        if (str_contains($causale, 'payout')) {
            return 'payout';
        }
        if (str_contains($causale, 'refund')) {
            return 'refund';
        }
        
        // Fallback finale basato su type generico
        return match($transaction->type) {
            'entrata' => 'charge',
            'bonifico' => 'transfer', // Default per bonifici è transfer, non payout
            'uscita' => 'refund',
            'addebito' => 'stripe_fee',
            default => 'charge',
        };
    }

    /**
     * Calcola i totali del report
     */
    public function calculateTotals(array $transactions): array
    {
        $totals = [
            'commissioni_riscosse' => 0, // application_fee
            'total_charge' => 0,
            'total_transfer' => 0,
            'total_payment' => 0, // Pagamenti sottoscrizione
            'commissioni_pagate' => 0, // stripe_fee + network_cost
            'total_coupon' => 0,
            'total_refund' => 0,
        ];

        foreach ($transactions as $transaction) {
            $amount = is_numeric($transaction->amount) ? abs((float) $transaction->amount) : abs((float) str_replace(',', '.', $transaction->amount));

            switch ($transaction->type) {
                case 'application_fee':
                    $totals['commissioni_riscosse'] += $amount;
                    break;
                case 'charge':
                    $totals['total_charge'] += $amount;
                    break;
                case 'transfer':
                    $totals['total_transfer'] += $amount;
                    break;
                case 'payment':
                    $totals['total_payment'] += $amount;
                    break;
                case 'stripe_fee':
                case 'network_cost':
                    $totals['commissioni_pagate'] += $amount;
                    break;
                case 'coupon':
                    $totals['total_coupon'] += $amount;
                    break;
                case 'refund':
                    $totals['total_refund'] += $amount;
                    break;
            }
        }

        // La differenza corretta considera le application_fee:
        // charge = transfer + application_fee + stripe_fee + coupon
        // quindi: charge - transfer - application_fee = stripe_fee + coupon (che sono già contati a parte)
        $totals['differenza'] = $totals['total_charge'] - $totals['total_transfer'] - $totals['commissioni_riscosse'];

        return $totals;
    }

    /**
     * Normalizza automaticamente le transazioni
     * - Converte charge in payment per bilanciare charge/transfer  
     * - Identifica coupon dalla piattaforma
     */
    public function normalizeTransactions(array $transactions): array
    {
        Log::info("=== NORMALIZZAZIONE AVVIATA ===", [
            'totale_transazioni' => count($transactions)
        ]);
        
        $normalized = [];

        // 1. Identifica transfer che sono coupon (dalla piattaforma)
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'transfer' && $this->isTransferACoupon($transaction)) {
                $transaction->correction_old_type = $transaction->type; // Salva tipo originale
                $transaction->type = 'coupon';
                $transaction->auto_corrected = true;
                $transaction->correction_reason = 'Transfer convertito in coupon (sconto piattaforma)';
            }
            $normalized[] = $transaction;
        }

        // 2. Calcola sbilanciamento tra charge e transfer
        $totalCharge = 0;
        $totalTransfer = 0;
        $totalApplicationFee = 0;
        foreach ($normalized as $t) {
            $amount = is_numeric($t->amount) ? (float) $t->amount : (float) str_replace(',', '.', $t->amount);
            if ($t->type === 'charge') {
                $totalCharge += abs($amount);
            } elseif ($t->type === 'transfer') {
                $totalTransfer += abs($amount);
            } elseif ($t->type === 'application_fee') {
                $totalApplicationFee += abs($amount);
            }
        }
        
        // La differenza VERA è: charge - transfer - application_fee
        // Perché: charge = transfer + application_fee + stripe_fee
        // Quindi: charge - transfer - application_fee = stripe_fee (che è pagato separatamente)
        $differenza = abs($totalCharge - $totalTransfer - $totalApplicationFee);
        
        Log::info("Totali calcolati", [
            'total_charge' => $totalCharge,
            'total_transfer' => $totalTransfer,
            'total_application_fee' => $totalApplicationFee,
            'differenza_netta' => $differenza,
            'note' => 'Differenza = charge - transfer - application_fee (dovrebbe essere ~0 o solo stripe_fee)'
        ]);
        
        // 3. Se c'è una differenza significativa (>1€), converti charge in payment
        // NOTA: La tolleranza è aumentata perché le stripe_fee vengono registrate separatamente
        if ($differenza > 1) {
            Log::info("Differenza > 1€, avvio conversione charges...");
            $normalized = $this->convertChargesToPayments($normalized, $differenza);
        } else {
            Log::info("Differenza < 1€, nessuna conversione necessaria");
        }

        return $normalized;
    }

    /**
     * Converte alcuni charge in payment per bilanciare con i transfer
     * I payment sono pagamenti di sottoscrizioni verso OPPLA, non hanno transfer corrispondente
     */
    private function convertChargesToPayments(array $transactions, float $targetAmount): array
    {
        $converted = [];
        $remainingToConvert = $targetAmount;
        $chargesChecked = 0;
        $chargesConverted = 0;
        $chargesWithTransfer = 0;
        
        Log::info("=== INIZIO CONVERSIONE CHARGES ===", [
            'target' => $targetAmount,
            'totale_transazioni' => count($transactions)
        ]);
        
        try {
            foreach ($transactions as $transaction) {
                // Cerca charge senza transfer corrispondente
                if ($transaction->type === 'charge' && $remainingToConvert > 0.5) {
                    $chargesChecked++;
                    $chargeAmount = is_numeric($transaction->amount) ? abs((float) $transaction->amount) : abs((float) str_replace(',', '.', $transaction->amount));
                    
                    Log::debug("Verifico charge #{$chargesChecked}", [
                        'id' => $transaction->transaction_id,
                        'amount' => $chargeAmount,
                        'date' => $transaction->created_at
                    ]);
                    
                    // Verifica se questo charge ha un transfer corrispondente
                    $hasMatchingTransfer = $this->hasMatchingTransfer($transaction, $transactions);
                    
                    if (!$hasMatchingTransfer) {
                        // Questo è probabilmente un payment (sottoscrizione)
                        Log::info("CONVERTENDO charge in payment", [
                            'id' => $transaction->transaction_id,
                            'amount' => $chargeAmount,
                            'remaining_before' => $remainingToConvert,
                        ]);
                        $transaction->correction_old_type = $transaction->type; // Salva tipo originale
                        $transaction->type = 'payment';
                        $transaction->auto_corrected = true;
                        $transaction->correction_reason = 'Charge convertito in payment (sottoscrizione/pagamento diretto)';
                        $remainingToConvert -= $chargeAmount;
                        $chargesConverted++;
                    } else {
                        Log::debug("❌ Charge ha transfer corrispondente, skip");
                        $chargesWithTransfer++;
                    }
                }
                
                $converted[] = $transaction;
            }
        } catch (\Exception $e) {
            Log::error("Errore durante conversione charges", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        Log::info("=== FINE CONVERSIONE ===", [
            'charges_controllati' => $chargesChecked,
            'charges_con_transfer' => $chargesWithTransfer,
            'charges_convertiti' => $chargesConverted,
            'rimanente' => $remainingToConvert
        ]);
        
        return $converted;
    }
    
    /**
     * Verifica se un charge ha un transfer corrispondente
     * METODO 1 (PREFERITO): Usa transfer_group se disponibile
     * METODO 2 (FALLBACK): Matching per importo e data
     * 
     * Logica:
     * - Se charge ha transfer_group → cerca transfer con stesso transfer_group
     * - Se charge NON ha transfer_group → è un payment (sottoscrizione)
     */
    private function hasMatchingTransfer($charge, array $transactions): bool
    {
        try {
            // Estrai transfer_group dal charge (se presente nel source_data)
            $chargeData = is_string($charge->source_data ?? null) 
                ? json_decode($charge->source_data, true) 
                : ($charge->source_data ?? []);
            
            $chargeTransferGroup = $chargeData['transfer_group'] ?? null;
            
            Log::debug("Cercando transfer per charge", [
                'charge_id' => $charge->transaction_id,
                'transfer_group' => $chargeTransferGroup ?? 'NESSUNO (è payment)',
            ]);
            
            // METODO 1: Se il charge ha un transfer_group, cerca un transfer con lo stesso gruppo
            if ($chargeTransferGroup) {
                foreach ($transactions as $t) {
                    if ($t->type === 'transfer') {
                        $transferData = is_string($t->source_data ?? null)
                            ? json_decode($t->source_data, true)
                            : ($t->source_data ?? []);
                        
                        $transferGroup = $transferData['transfer_group'] ?? null;
                        
                        // Match esatto sul transfer_group
                        if ($transferGroup && $transferGroup === $chargeTransferGroup) {
                            Log::debug("✓ Match trovato tramite transfer_group!", [
                                'transfer_id' => $t->transaction_id,
                                'transfer_group' => $transferGroup,
                            ]);
                            return true;
                        }
                    }
                }
                
                // Se ha transfer_group ma non troviamo match, potrebbe essere un dato mancante
                Log::warning("Charge ha transfer_group ma nessun transfer corrispondente trovato", [
                    'charge_id' => $charge->transaction_id,
                    'transfer_group' => $chargeTransferGroup,
                ]);
            } else {
                // Nessun transfer_group = questo è un payment (sottoscrizione o pagamento diretto)
                Log::debug("✓ Charge SENZA transfer_group → è un payment");
                return false;
            }
            
            // METODO 2 (FALLBACK): Se non abbiamo transfer_group, usa il vecchio metodo (per compatibilità con dati vecchi)
            Log::debug("Fallback: uso matching per data/importo...");
            
            $chargeAmount = is_numeric($charge->amount) ? abs((float) $charge->amount) : abs((float) str_replace(',', '.', $charge->amount));
            $chargeDate = Carbon::parse($charge->created_at);

            foreach ($transactions as $t) {
                if ($t->type === 'transfer') {
                    $transferAmount = is_numeric($t->amount) ? abs((float) $t->amount) : abs((float) str_replace(',', '.', $t->amount));
                    $transferDate = Carbon::parse($t->created_at);
                    
                    $amountDiff = abs($chargeAmount - $transferAmount);
                    $daysDiff = abs($chargeDate->diffInDays($transferDate));
                    
                    // Tolleranza aumentata a 10€ per coprire le commissioni Stripe
                    if ($daysDiff <= 2 && $amountDiff < 10.0) {
                        Log::debug("Match trovato con fallback!", [
                            'transfer_id' => $t->transaction_id,
                            'amount_diff' => $amountDiff,
                            'days_diff' => $daysDiff,
                        ]);
                        return true;
                    }
                }
            }
            
            Log::debug("Nessun match trovato - questo è probabilmente un payment");
            return false;
        } catch (\Exception $e) {
            Log::error('[StripeReport] Errore in hasMatchingTransfer', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Verifica se una transazione è un coupon della piattaforma
     * Un transfer è un coupon SE:
     * 1. Il suo stripe_source (tr_XXX) matcha stripe_platform_discount_transfer_id in un ordine
     * 2. E quell'ordine ha has_platform_discount = true
     */
    private function isTransferACoupon($transaction): bool
    {
        try {
            // Solo transfer possono essere coupon
            if ($transaction->type !== 'transfer') {
                return false;
            }
            
            $txnId = $transaction->transaction_id ?? '';
            $sourceData = is_string($transaction->source_data ?? null)
                ? json_decode($transaction->source_data, true)
                : ($transaction->source_data ?? []);
            $stripeSource = $sourceData['stripe_source'] ?? ''; // Es: tr_XXX
            
            // Deve essere un transfer (tr_)
            if (!str_starts_with($stripeSource, 'tr_') && !str_starts_with($txnId, 'tr_')) {
                return false;
            }
            
            $transferId = str_starts_with($stripeSource, 'tr_') ? $stripeSource : $txnId;
            
            // Cerca negli ordini locali se questo transfer_id è un platform discount
            $orderExists = DB::table('orders')
                ->whereBetween('order_date', ['2025-01-01', now()])
                ->whereNotNull('oppla_data')
                ->whereRaw("JSON_EXTRACT(oppla_data, '$.has_platform_discount') = true")
                ->whereRaw("JSON_EXTRACT(oppla_data, '$.stripe_platform_discount_transfer_id') = ?", [$transferId])
                ->exists();
            
            if ($orderExists) {
                Log::info('[Coupon] Transfer identificato come platform discount coupon', [
                    'transfer_id' => $transferId,
                ]);
            }
            
            return $orderExists;
        } catch (\Exception $e) {
            Log::warning('[Coupon] Errore verifica coupon: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bilancia charge/transfer con stessa data e importo (duplicati Stripe)
     * Stripe registra: charge (positivo) + transfer (negativo) per stessa transazione
     * Soluzione: elimina il transfer duplicato, mantiene solo il charge
     */
    private function balanceChargeTransfer(array $transactions): array
    {
        $balanced = [];
        $skipIndexes = [];

        foreach ($transactions as $index => $transaction) {
            if (in_array($index, $skipIndexes)) {
                continue;
            }

            // Se è un charge, cerca un transfer corrispondente
            if ($transaction->type === 'charge') {
                $matchingTransfer = $this->findMatchingTransfer($transaction, $transactions, $index);
                
                if ($matchingTransfer !== null) {
                    // Mantieni il charge, marca come corretto
                    $transaction->auto_corrected = true;
                    $transaction->correction_reason = 'Charge bilanciato - transfer duplicato rimosso';
                    
                    // Segna il transfer corrispondente per saltarlo (non verrà incluso nel report)
                    $skipIndexes[] = $matchingTransfer['index'];
                }
            }

            $balanced[] = $transaction;
        }

        return $balanced;
    }

    /**
     * Trova un transfer corrispondente a un charge (duplicato Stripe)
     * Charge = importo positivo, Transfer = importo negativo (stesso valore assoluto)
     */
    private function findMatchingTransfer($charge, array $transactions, int $currentIndex): ?array
    {
        $chargeAmount = is_numeric($charge->amount) ? abs((float) $charge->amount) : abs((float) str_replace(',', '.', $charge->amount));
        $chargeDate = Carbon::parse($charge->created_at);

        // Cerca nei 20 record circostanti (prima e dopo)
        $searchStart = max(0, $currentIndex - 10);
        $searchEnd = min(count($transactions), $currentIndex + 10);

        for ($i = $searchStart; $i < $searchEnd; $i++) {
            if ($i === $currentIndex) continue; // Salta se stesso

            $transaction = $transactions[$i];

            if ($transaction->type === 'transfer') {
                $transferAmount = is_numeric($transaction->amount) ? abs((float) $transaction->amount) : abs((float) str_replace(',', '.', $transaction->amount));
                $transferDate = Carbon::parse($transaction->created_at);

                // Stesso importo (±0.01€) e data/ora entro 5 minuti
                if (abs($chargeAmount - $transferAmount) < 0.01 && 
                    $chargeDate->diffInMinutes($transferDate) <= 5) {
                    return ['index' => $i, 'transaction' => $transaction];
                }
            }
        }

        return null;
    }

    /**
     * Ottieni le commissioni riscosse per ristorante (Fee Ristoranti)
     */
    public function getRestaurantFees(int $year, int $month): array
    {
        // TODO: Implementare se necessario usando bank_transactions
        return [];
    }

    /**
     * PLACEHOLDER - da rimuovere o implementare
     */
    private function OLD_getRestaurantFees_DISABLED(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Codice disabilitato - usava stripe_transactions che non esiste
        return [];
    }

    /**
     * Aggiorna manualmente il tipo di una transazione
     */
    public function updateTransactionType(string $transactionId, string $newType): bool
    {
        try {
            $transaction = DB::table('bank_transactions')
                ->where('source', 'stripe')
                ->where('source_transaction_id', $transactionId)
                ->first();
            
            if (!$transaction) {
                Log::warning('[StripeReport] Transazione non trovata per update', [
                    'transaction_id' => $transactionId,
                ]);
                return false;
            }
            
            // Decodifica source_data e note esistenti
            $sourceData = json_decode($transaction->source_data ?? '{}', true);
            $note = json_decode($transaction->note ?? '{}', true);
            
            // Aggiorna il tipo in ENTRAMBI i campi
            $sourceData['stripe_type'] = $newType;
            $note['stripe_type'] = $newType;
            $note['auto_corrected'] = true;
            $note['correction_date'] = now()->toDateTimeString();
            
            DB::table('bank_transactions')
                ->where('id', $transaction->id)
                ->update([
                    'source_data' => json_encode($sourceData),
                    'note' => json_encode($note),
                    'updated_at' => now()
                ]);
            
            Log::info('[StripeReport] Tipo transazione aggiornato', [
                'transaction_id' => $transactionId,
                'new_type' => $newType,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('[StripeReport] Errore aggiornamento tipo transazione', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
    
    /**
     * Ottieni l'email del commercialista salvata
     */
    public function getAccountantEmail(): ?string
    {
        try {
            $setting = DB::table('settings')
                ->where('key', 'accountant_email')
                ->first();
            
            return $setting?->value;
        } catch (\Exception $e) {
            Log::error('[StripeReport] Errore recupero email commercialista', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Salva l'email del commercialista
     */
    public function saveAccountantEmail(string $email): bool
    {
        try {
            DB::table('settings')->updateOrInsert(
                ['key' => 'accountant_email'],
                [
                    'value' => $email,
                    'updated_at' => now()
                ]
            );

            Log::info('[StripeReport] Email commercialista salvata', [
                'email' => $email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[StripeReport] Errore salvataggio email commercialista', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resetta tutte le normalizzazioni Stripe
     * Rimuove i campi stripe_type da source_data e note, forzando il sistema a re-determinare i tipi
     */
    public function resetNormalizations(): array
    {
        try {
            Log::info('[StripeReport] Inizio reset normalizzazioni');

            $transactions = DB::table('bank_transactions')
                ->where('source', 'stripe')
                ->get();

            $resetCount = 0;

            foreach ($transactions as $transaction) {
                $sourceData = json_decode($transaction->source_data ?? '{}', true);
                $note = json_decode($transaction->note ?? '{}', true);

                $modified = false;

                // Rimuovi stripe_type e campi di correzione da source_data
                if (isset($sourceData['stripe_type'])) {
                    unset($sourceData['stripe_type']);
                    $modified = true;
                }

                // Rimuovi tutti i campi di correzione dalle note
                $correctionFields = ['stripe_type', 'auto_corrected', 'correction_date', 'manually_corrected'];
                foreach ($correctionFields as $field) {
                    if (isset($note[$field])) {
                        unset($note[$field]);
                        $modified = true;
                    }
                }

                if ($modified) {
                    DB::table('bank_transactions')
                        ->where('id', $transaction->id)
                        ->update([
                            'source_data' => json_encode($sourceData),
                            'note' => json_encode($note),
                            'updated_at' => now()
                        ]);

                    $resetCount++;
                }
            }

            Log::info('[StripeReport] Reset completato', [
                'transazioni_resettate' => $resetCount,
            ]);

            return [
                'success' => true,
                'reset_count' => $resetCount,
                'total_transactions' => count($transactions),
            ];

        } catch (\Exception $e) {
            Log::error('[StripeReport] Errore reset normalizzazioni', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
