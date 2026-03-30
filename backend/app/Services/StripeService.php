<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\CheckoutSession;
use App\Models\Invoice;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private StripeClient $stripe;
    private ?BankAccount $bankAccount = null;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        
        // Trova o crea il conto bancario Stripe usando IBAN come identificatore univoco
        $this->bankAccount = BankAccount::where('iban', 'STRIPE_MAIN')->first();
        
        if (!$this->bankAccount) {
            // Crea nuovo account solo se non esiste
            try {
                $this->bankAccount = BankAccount::create([
                    'name' => 'Stripe Account',
                    'bank_name' => 'Stripe',
                    'type' => 'stripe',
                    'iban' => 'STRIPE_MAIN',
                    'saldo_iniziale' => 0,
                    'saldo_attuale' => 0,
                    'is_active' => true,
                    'auto_sync' => true,
                ]);
            } catch (\Exception $e) {
                Log::warning('Impossibile creare BankAccount per Stripe: ' . $e->getMessage());
                $this->bankAccount = null;
            }
        }
    }

    /**
     * Importa tutte le transazioni Stripe per un periodo
     */
    public function importTransactions(Carbon $startDate, Carbon $endDate, bool $forceFullSync = false, $command = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $updated = 0;

        // Verifica che il bankAccount esista
        if (!$this->bankAccount) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['BankAccount Stripe non disponibile'],
                'balance' => 0,
            ];
        }

        try {
            // SYNC INCREMENTALE: Trova l'ultima transazione Stripe nel DB (se non forzato sync completo)
            if (!$forceFullSync) {
                $lastTransaction = BankTransaction::where('source', 'stripe')
                    ->orderBy('transaction_date', 'desc')
                    ->first();
                
                if ($lastTransaction) {
                    // Se esiste, importa solo dal giorno successivo all'ultima transazione
                    $actualStartDate = Carbon::parse($lastTransaction->transaction_date)->addDay()->startOfDay();
                    
                    // Se la data calcolata è dopo endDate, non c'è nulla da importare
                    if ($actualStartDate->gt($endDate)) {
                        Log::info('[Stripe] Nessuna nuova transazione da importare', [
                            'last_transaction_date' => $lastTransaction->transaction_date,
                            'requested_end_date' => $endDate->toDateString(),
                        ]);
                        
                        return [
                            'imported' => 0,
                            'updated' => 0,
                            'skipped' => 0,
                            'errors' => [],
                            'balance' => $this->bankAccount->saldo_attuale ?? 0,
                            'message' => 'Tutte le transazioni sono già sincronizzate',
                            'last_sync_date' => $lastTransaction->transaction_date,
                        ];
                    }
                    
                    $startDate = $actualStartDate;
                    Log::info('[Stripe] Sync incrementale da ultima transazione', [
                        'last_transaction_date' => $lastTransaction->transaction_date,
                        'new_start_date' => $startDate->toDateString(),
                    ]);
                } else {
                    Log::info('[Stripe] Prima sincronizzazione completa - nessuna transazione precedente');
                }
            } else {
                Log::info('[Stripe] SYNC COMPLETO FORZATO - ignoro ultima transazione', [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ]);
            }
            
            // Importa TUTTE le Balance Transactions (include charges, fees, payouts, refunds, transfers, ecc.)
            // Ogni charge/payment genera automaticamente una balance transaction, quindi non serve importarli separatamente
            Log::info('[Stripe] Importing all balance transactions...', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
            
            $balanceTransactions = $this->stripe->balanceTransactions->all([
                'created' => [
                    'gte' => $startDate->timestamp,
                    'lte' => $endDate->timestamp,
                ],
                'limit' => 100,
                // Nessun filtro type - prende TUTTO
            ]);

            // Log per vedere TUTTI i tipi di transazioni
            $typeStats = [];
            
            foreach ($balanceTransactions->autoPagingIterator() as $transaction) {
                // Conta i tipi
                $typeStats[$transaction->type] = ($typeStats[$transaction->type] ?? 0) + 1;
                try {
                    // Verifica se già esiste
                    $exists = BankTransaction::where('source', 'stripe')
                        ->where('source_transaction_id', $transaction->id)
                        ->exists();
                    
                    $amount = $transaction->amount / 100;
                    $date = date('Y-m-d H:i:s', $transaction->created);
                    
                    if ($exists && !$forceFullSync) {
                        // In modalità incrementale, salta le esistenti
                        $skipped++;
                        if ($command) {
                            $command->line("  <fg=yellow>⏭️  SKIP</> [{$transaction->type}] {$transaction->id} - €{$amount} ({$date})");
                        }
                        continue;
                    } elseif ($exists && $forceFullSync) {
                        // In modalità FULL SYNC, aggiorna le esistenti
                        $this->createBankTransaction($transaction);
                        $updated++;
                        
                        if ($command) {
                            $command->line("  <fg=blue>🔄 UPDATE</> [{$transaction->type}] {$transaction->id} - €{$amount} ({$date})");
                        }
                    } else {
                        // Nuova transazione - importa
                        $this->createBankTransaction($transaction);
                        $imported++;
                        
                        if ($command) {
                            $typeIcon = match($transaction->type) {
                                'charge' => '💳',
                                'payment' => '💰',
                                'transfer' => '🔀',
                                'payout' => '💸',
                                'refund' => '↩️',
                                'adjustment' => '⚙️',
                                'application_fee' => '💵',
                                'stripe_fee' => '📊',
                                default => '📝'
                            };
                            $command->line("  <fg=green>NEW</> {$typeIcon} [{$transaction->type}] {$transaction->id} - €{$amount} ({$date})");
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Transaction {$transaction->id}: " . $e->getMessage();
                    if ($command) {
                        $command->error("  ❌ ERROR [{$transaction->type}] {$transaction->id} - " . $e->getMessage());
                    }
                    Log::error('Stripe import error', [
                        'transaction_id' => $transaction->id,
                        'type' => $transaction->type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Log statistiche tipi
            Log::info('[Stripe] Statistiche tipi transazioni ricevute da API:', $typeStats);

            // 2. Aggiorna il saldo del conto
            $balance = $this->stripe->balance->retrieve();
            $availableBalance = $balance->available[0]->amount / 100; // Converti da centesimi
            
            $this->bankAccount->update([
                'saldo_attuale' => $availableBalance,
                'saldo_data' => now(),
            ]);

            // 3. Crea estratto conto mensile
            // TODO: Fix BankStatement creation - requires month field
            // $this->createMonthlyStatement($startDate, $endDate);

        } catch (ApiErrorException $e) {
            $errors[] = "Stripe API Error: " . $e->getMessage();
            Log::error('Stripe API error', ['error' => $e->getMessage()]);
        }

        Log::info('[Stripe] Import completato', [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors_count' => count($errors),
        ]);

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'balance' => $this->bankAccount->saldo_attuale ?? 0,
            'type_stats' => $typeStats ?? [],
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Crea una transazione bancaria da Stripe Charge
     */
    private function createBankTransactionFromCharge($charge): BankTransaction
    {
        $customerEmail = null;
        $clientId = null;
        $restaurantName = null;
        
        // Recupera email del cliente
        try {
            if ($charge->customer && is_object($charge->customer)) {
                $customerEmail = $charge->customer->email;
            } elseif ($charge->billing_details && isset($charge->billing_details['email'])) {
                $customerEmail = $charge->billing_details['email'];
            } elseif ($charge->receipt_email) {
                $customerEmail = $charge->receipt_email;
            }
            
            // Estrai nome ristorante/partner dai metadati
            if ($charge->metadata) {
                $restaurantName = $charge->metadata['restaurant_name'] ?? 
                                 $charge->metadata['partner_name'] ?? 
                                 $charge->metadata['store_name'] ??
                                 $charge->metadata['merchant_name'] ?? null;
            }
            
            // Estrai transfer_destination se presente
            $transferDestination = null;
            $transferDestinationName = null;
            
            // Prova transfer_data.destination (Stripe Connect)
            if (isset($charge->transfer_data) && isset($charge->transfer_data->destination)) {
                $transferDestination = $charge->transfer_data->destination;
                
                // Prova a recuperare il nome dell'account connesso
                try {
                    $account = $this->stripe->accounts->retrieve($transferDestination);
                    $transferDestinationName = $account->business_profile->name ?? 
                                               $account->settings->dashboard->display_name ?? 
                                               $account->email ?? 
                                               $transferDestination;
                } catch (\Exception $e) {
                    Log::warning('[Stripe] Could not retrieve account details', [
                        'account_id' => $transferDestination,
                        'error' => $e->getMessage()
                    ]);
                    $transferDestinationName = $transferDestination;
                }
            } 
            // Prova on_behalf_of (pagamenti per conto di)
            elseif (isset($charge->on_behalf_of) && $charge->on_behalf_of) {
                $transferDestination = $charge->on_behalf_of;
                
                try {
                    $account = $this->stripe->accounts->retrieve($transferDestination);
                    $transferDestinationName = $account->business_profile->name ?? 
                                               $account->settings->dashboard->display_name ?? 
                                               $account->email ?? 
                                               $transferDestination;
                } catch (\Exception $e) {
                    Log::warning('[Stripe] Could not retrieve account details', [
                        'account_id' => $transferDestination,
                        'error' => $e->getMessage()
                    ]);
                    $transferDestinationName = $transferDestination;
                }
            }
            // Prova application_fee_amount (Platform fee)
            elseif (isset($charge->application) && $charge->application) {
                $transferDestination = $charge->application;
                $transferDestinationName = $transferDestination;
            }
            
            // Se non c'è destinazione specifica, usa il metadata se disponibile
            if (!$transferDestination && isset($charge->metadata) && is_object($charge->metadata)) {
                $metadataArray = (array) $charge->metadata;
                $transferDestination = $metadataArray['destination_account'] ?? 
                                      $metadataArray['connected_account'] ?? null;
                $transferDestinationName = $transferDestination;
            }
            
            // Se c'è un ristorante, crea/trova il cliente dal nome del ristorante
            if ($restaurantName) {
                $clientId = $this->findOrCreateClientFromBeneficiary($restaurantName);
            } elseif ($customerEmail) {
                // Altrimenti usa la email del cliente
                $clientId = $this->findOrCreateClientFromEmail($customerEmail);
            }
        } catch (\Exception $e) {
            Log::warning('[Stripe] Errore recupero customer email: ' . $e->getMessage());
        }

        $amount = $charge->amount / 100;
        $fee = ($charge->application_fee_amount ?? 0) / 100;
        $net = $charge->amount_refunded > 0 ? ($charge->amount - $charge->amount_refunded) / 100 : $amount;
        
        $description = 'Pagamento ricevuto';
        if ($charge->description) {
            $description .= ': ' . $charge->description;
        }
        if ($charge->metadata && isset($charge->metadata['order_id'])) {
            $description .= ' (Ordine #' . $charge->metadata['order_id'] . ')';
        }

        return BankTransaction::updateOrCreate(
            [
                'source' => 'stripe',
                'source_transaction_id' => $charge->id,
            ],
            [
                'bank_account_id' => $this->bankAccount->id,
                'transaction_date' => Carbon::createFromTimestamp($charge->created),
                'value_date' => Carbon::createFromTimestamp($charge->created),
                'type' => $charge->refunded ? 'uscita' : 'entrata',
                'amount' => $charge->refunded ? -$amount : $amount,
                'fee' => $fee,
                'net_amount' => $net,
                'gross_amount' => $amount,
                'currency' => strtoupper($charge->currency),
                'descrizione' => $description,
                'causale' => 'Stripe Payment: ' . ($charge->payment_method_details->type ?? 'card'),
                'beneficiario' => $restaurantName ?? $customerEmail ?? ($charge->billing_details->name ?? 'Cliente Stripe'),
                'normalized_beneficiary' => $restaurantName ? $this->normalizeBeneficiary($restaurantName) : ($customerEmail ? $this->normalizeEmail($customerEmail) : null),
                'client_id' => $clientId,
                'is_reconciled' => false,
                'category' => 'stripe',
                'source_data' => json_encode([
                    'transfer_destination' => $transferDestinationName ?? $transferDestination,
                    'transfer_destination_id' => $transferDestination,
                    'restaurant_name' => $restaurantName,
                    'customer_email' => $customerEmail,
                ]),
                'note' => json_encode([
                    'stripe_id' => $charge->id,
                    'stripe_source' => $charge->id,
                    'payment_method' => $charge->payment_method_details->type ?? null,
                    'status' => $charge->status,
                    'captured' => $charge->captured,
                    'refunded' => $charge->refunded,
                    'amount_refunded' => $charge->amount_refunded / 100,
                ]),
            ]
        );
    }

    /**
     * Crea una transazione bancaria da Stripe Balance Transaction
     */
    private function createBankTransaction($stripeTransaction): BankTransaction
    {
        // Trova la fattura associata se esiste
        $invoice = null;
        $customerEmail = null;
        $clientId = null;
        $beneficiario = null;
        $transferGroup = null;
        
        if ($stripeTransaction->source && str_starts_with($stripeTransaction->source, 'ch_')) {
            // È un charge, cerca la fattura associata e l'email del cliente
            try {
                $charge = $this->stripe->charges->retrieve($stripeTransaction->source, ['expand' => ['customer', 'payment_intent']]);
                
                if ($charge->metadata && isset($charge->metadata['invoice_id'])) {
                    $invoice = Invoice::find($charge->metadata['invoice_id']);
                }
                
                // Recupera email dal customer Stripe
                if ($charge->customer && is_object($charge->customer)) {
                    $customerEmail = $charge->customer->email;
                } elseif ($charge->billing_details && isset($charge->billing_details['email'])) {
                    $customerEmail = $charge->billing_details['email'];
                } elseif ($charge->receipt_email) {
                    $customerEmail = $charge->receipt_email;
                }
                
                // Trova o crea il client dalla email
                if ($customerEmail) {
                    $clientId = $this->findOrCreateClientFromEmail($customerEmail);
                }
                
                // NUOVO: Recupera transfer_group dal charge o dal payment_intent
                $transferGroup = $charge->transfer_group ?? null;
                if (!$transferGroup && isset($charge->payment_intent) && is_object($charge->payment_intent)) {
                    $transferGroup = $charge->payment_intent->transfer_group ?? null;
                }
            } catch (\Exception $e) {
                Log::warning('[Stripe] Errore recupero customer email: ' . $e->getMessage());
            }
        } elseif ($stripeTransaction->source && str_starts_with($stripeTransaction->source, 'tr_')) {
            // È un transfer, recupera il transfer_group
            try {
                $transfer = $this->stripe->transfers->retrieve($stripeTransaction->source);
                $transferGroup = $transfer->transfer_group ?? null;
            } catch (\Exception $e) {
                Log::warning('[Stripe] Errore recupero transfer group: ' . $e->getMessage());
            }
        } elseif ($stripeTransaction->type === 'payout' && $stripeTransaction->description) {
            // Per i payouts (bonifici), usa la description come beneficiario
            $beneficiario = $stripeTransaction->description;
            
            // Crea o trova il cliente dal nome beneficiario
            $clientId = $this->findOrCreateClientFromBeneficiary($beneficiario);
        }

        $amount = $stripeTransaction->net / 100; // Converti da centesimi
        $fee = $stripeTransaction->fee / 100;
        $gross = $stripeTransaction->amount / 100;

        // Determina il tipo in base al tipo Stripe - GESTISCE TUTTI I TIPI
        $type = match($stripeTransaction->type) {
            'charge', 'payment', 'payment_refund', 'application_fee' => 'entrata',
            'payout', 'payout_cancel', 'transfer' => 'bonifico',
            'refund', 'payment_failure_refund' => 'uscita',
            'stripe_fee', 'network_cost', 'application_fee_refund' => 'addebito',
            'adjustment', 'validation', 'contribution' => 'altro',
            default => 'altro',
        };
        
        // Descrizione del tipo per note
        $stripeTypeLabel = $stripeTransaction->type;

        // Per i payout usa available_on, per il resto usa created
        $transactionDate = isset($stripeTransaction->available_on) 
            ? Carbon::createFromTimestamp($stripeTransaction->available_on)
            : Carbon::createFromTimestamp($stripeTransaction->created);

        return BankTransaction::updateOrCreate(
            [
                'source' => 'stripe',
                'source_transaction_id' => $stripeTransaction->id,
            ],
            [
                'bank_account_id' => $this->bankAccount->id,
                'transaction_date' => $transactionDate,
                'value_date' => $transactionDate,
                'type' => $type,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $amount,
                'currency' => strtoupper($stripeTransaction->currency),
                'descrizione' => $this->getTransactionDescription($stripeTransaction),
                'causale' => 'Stripe: ' . $stripeTransaction->type,
                'beneficiario' => $beneficiario ?? $customerEmail ?? ($stripeTransaction->description ?? 'Stripe Transaction'),
                'normalized_beneficiary' => $beneficiario ? $this->normalizeBeneficiary($beneficiario) : ($customerEmail ? $this->normalizeEmail($customerEmail) : null),
                'client_id' => $clientId,
                'is_reconciled' => $invoice !== null,
                'invoice_id' => $invoice?->id,
                'category' => 'stripe',
                'source_data' => json_encode([
                    'stripe_type' => $stripeTransaction->type,
                    'stripe_source' => $stripeTransaction->source,
                    'reporting_category' => $stripeTransaction->reporting_category ?? null,
                    'transfer_group' => $transferGroup,
                ]),
                'note' => json_encode([
                    'stripe_id' => $stripeTransaction->id,
                    'fee' => $fee,
                    'gross' => $gross,
                    'stripe_type' => $stripeTransaction->type,
                    'stripe_source' => $stripeTransaction->source,
                    'status' => $stripeTransaction->status,
                    'description' => $stripeTransaction->description ?? null,
                ]),
            ]
        );
    }

    /**
     * Genera una descrizione leggibile
     */
    private function getTransactionDescription($transaction): string
    {
        $descriptions = [
            'charge' => 'Pagamento ricevuto',
            'payment' => 'Pagamento online',
            'payout' => 'Bonifico su conto corrente',
            'refund' => 'Rimborso cliente',
            'adjustment' => 'Rettifica Stripe',
            'application_fee' => 'Commissione applicazione',
            'stripe_fee' => 'Commissione Stripe',
        ];

        $base = $descriptions[$transaction->type] ?? 'Transazione Stripe';
        
        if ($transaction->description) {
            return $base . ' - ' . $transaction->description;
        }

        return $base;
    }

    /**
     * Crea l'estratto conto mensile
     */
    private function createMonthlyStatement(Carbon $startDate, Carbon $endDate): BankStatement
    {
        $transactions = BankTransaction::where('bank_account_id', $this->bankAccount->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $totalIncome = $transactions->where('type', 'entrata')->sum('amount');
        $totalExpenses = $transactions->whereIn('type', ['uscita', 'bonifico'])->sum('amount');
        $netAmount = $totalIncome - abs($totalExpenses);

        return BankStatement::updateOrCreate(
            [
                'bank_account_id' => $this->bankAccount->id,
                'month' => $endDate->month,
                'year' => $endDate->year,
            ],
            [
                'period_start' => $startDate,
                'period_end' => $endDate,
                'saldo_iniziale' => $this->bankAccount->saldo_attuale - $netAmount,
                'saldo_finale' => $this->bankAccount->saldo_attuale,
                'totale_entrate' => $totalIncome,
                'totale_uscite' => abs($totalExpenses),
                'status' => 'imported',
                'imported_at' => now(),
            ]
        );
    }

    /**
     * Riconcilia automaticamente le transazioni con le fatture
     */
    public function reconcileTransactions(Carbon $startDate, Carbon $endDate): int
    {
        $reconciled = 0;

        $unreconciled = BankTransaction::where('bank_account_id', $this->bankAccount->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_reconciled', false)
            ->where('type', 'income')
            ->get();

        foreach ($unreconciled as $transaction) {
            // Cerca fatture non riconciliate con importo simile
            $invoice = Invoice::where('payment_method', 'online')
                ->where('status', 'paid')
                ->whereNull('bank_transaction_id')
                ->whereBetween('total', [
                    $transaction->gross_amount - 0.50, // Tolleranza 50 centesimi
                    $transaction->gross_amount + 0.50,
                ])
                ->whereBetween('invoice_date', [
                    $transaction->transaction_date->subDays(2),
                    $transaction->transaction_date->addDays(2),
                ])
                ->first();

            if ($invoice) {
                $transaction->update([
                    'invoice_id' => $invoice->id,
                    'is_reconciled' => true,
                ]);

                $invoice->update([
                    'bank_transaction_id' => $transaction->id,
                ]);

                $reconciled++;
            }
        }

        return $reconciled;
    }

    /**
     * Ottieni il saldo disponibile
     */
    public function getBalance(): array
    {
        try {
            $balance = $this->stripe->balance->retrieve();
            
            return [
                'available' => $balance->available[0]->amount / 100,
                'pending' => $balance->pending[0]->amount / 100,
                'currency' => strtoupper($balance->available[0]->currency),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe balance error', ['error' => $e->getMessage()]);
            return [
                'available' => 0,
                'pending' => 0,
                'currency' => 'EUR',
            ];
        }
    }

    /**
     * Trova un cliente dalla email
     * Se non esiste, ritorna null (NON crea titolari placeholder)
     */
    private function findOrCreateClientFromEmail(string $email): ?int
    {
        // Cerca cliente esistente con questa email
        $client = Client::where('email', $email)->first();
        
        if ($client) {
            return $client->id;
        }
        
        // NON creare più titolari placeholder "Da completare"
        // Ritorna null e il pagamento rimarrà senza cliente finché non viene assegnato manualmente
        Log::info('[Stripe] Cliente non trovato per email: ' . $email . ' - pagamento importato senza cliente');
        
        return null;
    }

    /**
     * Normalizza email per matching
     */
    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Ottieni le ultime transazioni (real-time)
     */
    public function getRecentTransactions(int $limit = 20): array
    {
        try {
            $transactions = $this->stripe->balanceTransactions->all([
                'limit' => $limit,
            ]);

            $result = [];
            foreach ($transactions->data as $transaction) {
                $result[] = [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount / 100,
                    'net' => $transaction->net / 100,
                    'fee' => $transaction->fee / 100,
                    'currency' => strtoupper($transaction->currency),
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'created' => Carbon::createFromTimestamp($transaction->created)->toDateTimeString(),
                    'status' => $transaction->status,
                ];
            }

            return $result;
        } catch (ApiErrorException $e) {
            Log::error('Stripe recent transactions error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Crea un rimborso su Stripe
     */
    public function createRefund(string $chargeId, ?float $amount = null, string $reason = 'requested_by_customer'): array
    {
        try {
            $params = [
                'charge' => $chargeId,
                'reason' => $reason,
            ];

            // Se amount è specificato, converti in centesimi
            if ($amount !== null) {
                $params['amount'] = (int)($amount * 100);
            }

            $refund = $this->stripe->refunds->create($params);

            return [
                'id' => $refund->id,
                'amount' => $refund->amount / 100,
                'currency' => strtoupper($refund->currency),
                'status' => $refund->status,
                'reason' => $refund->reason,
                'created' => Carbon::createFromTimestamp($refund->created)->toDateTimeString(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund error', ['error' => $e->getMessage(), 'charge_id' => $chargeId]);
            throw new \Exception('Errore Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Esporta estratto conto in Excel
     */
    public function exportStatement(Carbon $startDate, Carbon $endDate): string
    {
        $transactions = BankTransaction::where('bank_account_id', $this->bankAccount->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        $filename = 'stripe_statement_' . $startDate->format('Y-m') . '.csv';
        $path = storage_path('app/exports/' . $filename);

        // Crea la directory se non esiste
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $fp = fopen($path, 'w');
        
        // Header CSV
        fputcsv($fp, [
            'Data',
            'Descrizione',
            'Tipo',
            'Lordo',
            'Commissioni',
            'Netto',
            'Valuta',
            'ID Transazione',
            'Fattura',
            'Stato',
        ]);

        foreach ($transactions as $transaction) {
            fputcsv($fp, [
                $transaction->transaction_date->format('d/m/Y H:i'),
                $transaction->description,
                $transaction->type,
                number_format($transaction->gross_amount, 2, ',', '.'),
                number_format($transaction->fee, 2, ',', '.'),
                number_format($transaction->amount, 2, ',', '.'),
                $transaction->currency,
                $transaction->transaction_id,
                $transaction->invoice?->invoice_number ?? '-',
                $transaction->is_reconciled ? 'Riconciliato' : 'Da verificare',
            ]);
        }

        fclose($fp);

        return $filename;
    }

    /**
     * Normalizza beneficiario per matching
     */
    private function normalizeBeneficiary(string $beneficiary): string
    {
        // Rimuove spazi multipli, lowercase, trim
        return strtolower(trim(preg_replace('/\s+/', ' ', $beneficiary)));
    }

    /**
     * Trova o crea un cliente dal nome beneficiario
     */
    private function findOrCreateClientFromBeneficiary(string $beneficiary): ?int
    {
        // Prima prova a trovare un cliente esistente
        $normalized = $this->normalizeBeneficiary($beneficiary);
        
        $client = Client::where('ragione_sociale', 'LIKE', $beneficiary)
            ->orWhere('ragione_sociale', 'LIKE', $normalized)
            ->first();
        
        if ($client) {
            return $client->id;
        }
        
        // Crea nuovo cliente con il nome del beneficiario
        try {
            $newClient = Client::create([
                'ragione_sociale' => $beneficiary,
                'tipo' => 'partner', // Tipo predefinito per beneficiari
                'source' => 'stripe_auto',
                'note' => 'Cliente creato automaticamente da Stripe (bonifico)',
            ]);
            
            Log::info('[Stripe] Cliente creato da beneficiario', [
                'beneficiary' => $beneficiary,
                'client_id' => $newClient->id,
            ]);
            
            return $newClient->id;
        } catch (\Exception $e) {
            Log::error('[Stripe] Errore creazione cliente da beneficiario', [
                'beneficiary' => $beneficiary,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Recupera commissioni riscosse (application fees) dal DATABASE
     * Le commissioni vengono sincronizzate con il comando stripe:import
     * 
     * @param Carbon $startDate Data inizio periodo
     * @param Carbon $endDate Data fine periodo
     * @return array Array di commissioni con dettagli partner
     */
    public function getApplicationFees(Carbon $startDate, Carbon $endDate): array
    {
        try {
            Log::info('[Stripe] Recupero commissioni riscosse dal database', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            // Genera lista di period_month nel range richiesto
            $periodMonths = [];
            $current = $startDate->copy()->startOfMonth();
            $end = $endDate->copy()->startOfMonth();
            
            while ($current->lte($end)) {
                $periodMonths[] = $current->format('Y-m');
                $current->addMonth();
            }

            // Recupera dal database filtrando per period_month (più affidabile di created_at_stripe)
            $fees = \App\Models\ApplicationFee::whereIn('period_month', $periodMonths)
                ->orderBy('created_at_stripe', 'desc')
                ->get()
                ->map(function ($fee) {
                    $createdAt = Carbon::parse($fee->created_at_stripe);
                    return [
                        'id' => $fee->stripe_fee_id,
                        'amount' => (float) $fee->amount,
                        'currency' => strtoupper($fee->currency),
                        'created' => $createdAt->toIso8601String(), // Formato ISO per frontend
                        'created_timestamp' => $createdAt->timestamp, // Timestamp per ordinamento
                        'stripe_account_id' => $fee->stripe_account_id,
                        'partner_email' => $fee->partner_email,
                        'partner_name' => $fee->partner_name,
                        'client_id' => $fee->client_id,
                        'charge_id' => $fee->charge_id,
                        'description' => $fee->description,
                        'period_month' => $fee->period_month,
                    ];
                })
                ->toArray();

            Log::info('[Stripe] Commissioni riscosse recuperate dal database', [
                'count' => count($fees),
                'period_months' => $periodMonths,
            ]);

            return $fees;
        } catch (\Exception $e) {
            Log::error('[Stripe] Errore recupero commissioni dal database', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sincronizza application fees da Stripe API al database
     * Chiamato dal comando stripe:import
     * 
     * @param Carbon $startDate Data inizio sincronizzazione
     * @param Carbon $endDate Data fine sincronizzazione
     * @return array Statistiche sincronizzazione
     */
    public function syncApplicationFees(Carbon $startDate, Carbon $endDate): array
    {
        try {
            Log::info('[Stripe] Inizio sincronizzazione application fees', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);

            set_time_limit(600); // 10 minuti per sync (aumentato per grandi volumi)

            $imported = 0;
            $skipped = 0;
            $errors = 0;
            $hasMore = true;
            $startingAfter = null;

            // Paginazione manuale per gestire grandi volumi
            while ($hasMore) {
                $params = [
                    'created' => [
                        'gte' => $startDate->timestamp,
                        'lte' => $endDate->timestamp,
                    ],
                    'limit' => 100,
                ];

                if ($startingAfter) {
                    $params['starting_after'] = $startingAfter;
                }

                $applicationFees = $this->stripe->applicationFees->all($params);

                foreach ($applicationFees->data as $fee) {
                    try {
                        // Verifica se esiste già
                        $existing = \App\Models\ApplicationFee::where('stripe_fee_id', $fee->id)->first();
                        if ($existing) {
                            $skipped++;
                            continue;
                        }

                        // Recupera account Stripe Connect del partner
                        $account = $fee->account;
                        $accountId = is_string($account) ? $account : $account->id;
                        
                        // Recupera dettagli account per email
                        $accountDetails = $this->stripe->accounts->retrieve($accountId);
                        $partnerEmail = $accountDetails->email ?? null;

                        // Cerca cliente nel database per email
                        $client = null;
                        if ($partnerEmail) {
                            $client = Client::where('email', $partnerEmail)->first();
                        }

                        // Salva nel database
                        \App\Models\ApplicationFee::create([
                            'stripe_fee_id' => $fee->id,
                            'amount' => $fee->amount / 100, // Converti da centesimi
                            'currency' => strtoupper($fee->currency),
                            'created_at_stripe' => Carbon::createFromTimestamp($fee->created),
                            'stripe_account_id' => $accountId,
                            'partner_email' => $partnerEmail,
                            'partner_name' => $client?->ragione_sociale ?? $accountDetails->business_profile?->name ?? 'Partner Sconosciuto',
                            'client_id' => $client?->id,
                            'charge_id' => is_string($fee->charge) ? $fee->charge : $fee->charge?->id,
                            'description' => "{$partnerEmail} - {$accountId}",
                            'period_month' => Carbon::createFromTimestamp($fee->created)->format('Y-m'),
                            'raw_data' => json_decode(json_encode($fee), true),
                        ]);

                        $imported++;
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('[Stripe] Errore sincronizzazione application fee', [
                            'fee_id' => $fee->id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $hasMore = $applicationFees->has_more;
                if ($hasMore && count($applicationFees->data) > 0) {
                    $startingAfter = end($applicationFees->data)->id;
                }
            }

            Log::info('[Stripe] Sincronizzazione application fees completata', [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (ApiErrorException $e) {
            Log::error('[Stripe] Errore API sincronizzazione application fees', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aggrega commissioni riscosse per partner
     * 
     * @param array $fees Array di commissioni da getApplicationFees()
     * @param bool $groupByOwner Se true, raggruppa per titolare (client_id)
     * @return array Commissioni aggregate
     */
    public function aggregateApplicationFees(array $fees, bool $groupByOwner = false): array
    {
        if ($groupByOwner) {
            // Raggruppa per client_id (titolare)
            $grouped = [];
            foreach ($fees as $fee) {
                if (!$fee['client_id']) {
                    // Se non c'è client_id, raggruppa per email
                    $key = 'no_client_' . ($fee['partner_email'] ?? 'unknown');
                } else {
                    $key = 'client_' . $fee['client_id'];
                }

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'client_id' => $fee['client_id'],
                        'partner_name' => $fee['partner_name'],
                        'partner_email' => $fee['partner_email'],
                        'total_amount' => 0,
                        'transaction_count' => 0,
                        'period_month' => $fee['period_month'],
                        'first_transaction' => $fee['created'],
                        'last_transaction' => $fee['created'],
                        'fees' => [],
                    ];
                }

                $grouped[$key]['total_amount'] += $fee['amount'];
                $grouped[$key]['transaction_count']++;
                $grouped[$key]['fees'][] = $fee;
                
                // Converti string a Carbon per confronto date
                $createdDate = is_string($fee['created']) ? Carbon::parse($fee['created']) : $fee['created'];
                $firstDate = is_string($grouped[$key]['first_transaction']) ? Carbon::parse($grouped[$key]['first_transaction']) : $grouped[$key]['first_transaction'];
                $lastDate = is_string($grouped[$key]['last_transaction']) ? Carbon::parse($grouped[$key]['last_transaction']) : $grouped[$key]['last_transaction'];
                
                if ($createdDate->lt($firstDate)) {
                    $grouped[$key]['first_transaction'] = $fee['created'];
                }
                if ($createdDate->gt($lastDate)) {
                    $grouped[$key]['last_transaction'] = $fee['created'];
                }
            }

            return array_values($grouped);
        } else {
            // Raggruppa per email + periodo
            $grouped = [];
            foreach ($fees as $fee) {
                $key = ($fee['partner_email'] ?? 'unknown') . '_' . $fee['period_month'];

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'client_id' => $fee['client_id'],
                        'partner_name' => $fee['partner_name'],
                        'partner_email' => $fee['partner_email'],
                        'stripe_account_id' => $fee['stripe_account_id'],
                        'total_amount' => 0,
                        'transaction_count' => 0,
                        'period_month' => $fee['period_month'],
                        'first_transaction' => $fee['created'],
                        'last_transaction' => $fee['created'],
                        'fees' => [],
                    ];
                }

                $grouped[$key]['total_amount'] += $fee['amount'];
                $grouped[$key]['transaction_count']++;
                $grouped[$key]['fees'][] = $fee;
                
                // Converti string a Carbon per confronto date
                $createdDate = is_string($fee['created']) ? Carbon::parse($fee['created']) : $fee['created'];
                $firstDate = is_string($grouped[$key]['first_transaction']) ? Carbon::parse($grouped[$key]['first_transaction']) : $grouped[$key]['first_transaction'];
                $lastDate = is_string($grouped[$key]['last_transaction']) ? Carbon::parse($grouped[$key]['last_transaction']) : $grouped[$key]['last_transaction'];
                
                if ($createdDate->lt($firstDate)) {
                    $grouped[$key]['first_transaction'] = $fee['created'];
                }
                if ($createdDate->gt($lastDate)) {
                    $grouped[$key]['last_transaction'] = $fee['created'];
                }
            }

            return array_values($grouped);
        }
    }

    /**
     * Crea una Checkout Session Stripe per generare un link di pagamento
     */
    public function createCheckoutSession(float $amount, string $description, ?string $customerEmail = null): CheckoutSession
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        $params = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $description,
                    ],
                    'unit_amount' => (int) round($amount * 100), // Stripe usa centesimi
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/payments?checkout=success',
            'cancel_url' => $frontendUrl . '/payments?checkout=cancel',
        ];

        if ($customerEmail) {
            $params['customer_email'] = $customerEmail;
        }

        $session = $this->stripe->checkout->sessions->create($params);

        $checkoutSession = CheckoutSession::create([
            'stripe_session_id' => $session->id,
            'amount' => $amount,
            'currency' => 'EUR',
            'description' => $description,
            'status' => $session->status ?? 'open',
            'payment_url' => $session->url,
            'customer_email' => $customerEmail,
            'expires_at' => Carbon::createFromTimestamp($session->expires_at),
        ]);

        Log::info('[StripeService] Checkout session creata', [
            'session_id' => $session->id,
            'amount' => $amount,
            'description' => $description,
        ]);

        return $checkoutSession;
    }

    /**
     * Lista checkout sessions dal DB
     */
    public function listCheckoutSessions(?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CheckoutSession::orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Aggiorna lo stato di una checkout session dalla API Stripe
     */
    public function refreshCheckoutSessionStatus(string $stripeSessionId): CheckoutSession
    {
        $session = $this->stripe->checkout->sessions->retrieve($stripeSessionId);

        $checkoutSession = CheckoutSession::where('stripe_session_id', $stripeSessionId)->firstOrFail();

        $checkoutSession->update([
            'status' => $session->status === 'complete' ? 'complete' : ($session->status === 'expired' ? 'expired' : 'open'),
            'payment_intent_id' => $session->payment_intent,
            'completed_at' => $session->status === 'complete' ? now() : null,
        ]);

        return $checkoutSession->fresh();
    }
}
