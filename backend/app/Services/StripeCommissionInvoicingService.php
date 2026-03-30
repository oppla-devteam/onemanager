<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ApplicationFee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servizio per fatturazione differita commissioni Stripe (application fees)
 * 
 * Flusso:
 * 1. Recupera commissioni riscosse da Stripe per il mese precedente
 * 2. Raggruppa per email partner o titolare account
 * 3. Genera fattura differita per ogni partner
 * 4. Include coupon piattaforma con segno negativo
 * 5. Invia fattura a Fatture in Cloud
 */
class StripeCommissionInvoicingService
{
    protected $stripeService;
    protected $ficService;

    public function __construct(StripeService $stripeService, FattureInCloudService $ficService)
    {
        $this->stripeService = $stripeService;
        $this->ficService = $ficService;
    }

    /**
     * Genera fatture differite per tutte le commissioni Stripe del mese
     */
    public function generateMonthlyCommissionInvoices(int $month, int $year): array
    {
        // RIMUOVERE LA TRANSAZIONE GLOBALE - ogni fattura ha la sua transazione
        // per permettere al lock di funzionare correttamente
        
        try {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            Log::info('[StripeCommissionInvoicing] Inizio generazione fatture commissioni', [
                'month' => $month,
                'year' => $year,
            ]);

            // 1. Recupera commissioni dal database locale (ApplicationFee)
            $periodMonth = sprintf('%04d-%02d', $year, $month);
            $fees = ApplicationFee::where('period_month', $periodMonth)
                ->orderBy('created_at_stripe', 'asc')
                ->get()
                ->map(function ($fee) {
                    return [
                        'id' => $fee->stripe_fee_id,
                        'amount' => $fee->amount,
                        'currency' => $fee->currency,
                        'created' => $fee->created_at_stripe,
                        'partner_email' => $fee->partner_email,
                        'partner_name' => $fee->partner_name,
                        'partner_id' => $fee->client_id,
                        'charge_id' => $fee->charge_id,
                        'description' => $fee->description,
                    ];
                })
                ->toArray();

            if (empty($fees)) {
                Log::info('[StripeCommissionInvoicing] Nessuna commissione trovata per il periodo');
                return [];
            }

            // 2. Recupera eventuali coupon piattaforma dal database OPPLA
            $platformCoupons = $this->getPlatformCoupons($month, $year);

            // 3. Raggruppa commissioni per partner (email)
            $grouped = $this->groupFeesByPartner($fees);

            $invoices = [];

            foreach ($grouped as $partnerEmail => $partnerData) {
                try {
                    // Trova o crea cliente per questo partner
                    $client = $this->findOrCreateClientForPartner($partnerEmail, $partnerData);

                    if (!$client) {
                        Log::warning('[StripeCommissionInvoicing] Partner non associato a cliente', [
                            'partner_email' => $partnerEmail,
                        ]);
                        continue;
                    }

                    // Genera fattura differita (ha la sua transazione interna)
                    $invoice = $this->generatePartnerCommissionInvoice(
                        $client,
                        $partnerData,
                        $platformCoupons[$partnerEmail] ?? [],
                        $month,
                        $year
                    );

                    if ($invoice) {
                        $invoices[] = $invoice;
                    }

                } catch (\Exception $e) {
                    Log::error('[StripeCommissionInvoicing] Errore fatturazione partner', [
                        'partner_email' => $partnerEmail,
                        'error' => $e->getMessage(),
                    ]);
                    // Continua con il prossimo partner (non blocca tutto)
                }
            }

            Log::info('[StripeCommissionInvoicing] Fatture generate con successo', [
                'count' => count($invoices),
                'month' => $month,
                'year' => $year,
            ]);

            return $invoices;

        } catch (\Exception $e) {
            Log::error('[StripeCommissionInvoicing] Errore generazione fatture: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Genera singola fattura per un partner specifico
     */
    public function generateSinglePartnerInvoice(int $month, int $year, string $partnerEmail, int $clientId): ?Invoice
    {
        DB::beginTransaction();

        try {
            // Recupera commissioni per questo partner
            $periodMonth = sprintf('%04d-%02d', $year, $month);
            $fees = ApplicationFee::where('period_month', $periodMonth)
                ->where('partner_email', $partnerEmail)
                ->orderBy('created_at_stripe', 'asc')
                ->get()
                ->map(function ($fee) {
                    return [
                        'id' => $fee->stripe_fee_id,
                        'amount' => $fee->amount,
                        'currency' => $fee->currency,
                        'created' => $fee->created_at_stripe,
                        'partner_email' => $fee->partner_email,
                        'partner_name' => $fee->partner_name,
                        'partner_id' => $fee->client_id,
                        'charge_id' => $fee->charge_id,
                        'description' => $fee->description,
                    ];
                })
                ->toArray();

            if (empty($fees)) {
                Log::warning('[StripeCommissionInvoicing] Nessuna commissione trovata per partner', [
                    'partner_email' => $partnerEmail,
                    'period' => $periodMonth,
                ]);
                DB::rollBack();
                return null;
            }

            // Raggruppa (sarà un solo gruppo)
            $grouped = $this->groupFeesByPartner($fees);
            $partnerData = $grouped[$partnerEmail] ?? null;

            if (!$partnerData) {
                DB::rollBack();
                return null;
            }

            // Recupera cliente
            $client = Client::find($clientId);
            if (!$client) {
                throw new \Exception("Cliente non trovato: {$clientId}");
            }

            // Recupera coupon
            $platformCoupons = $this->getPlatformCoupons($month, $year);

            // Genera fattura
            $invoice = $this->generatePartnerCommissionInvoice(
                $client,
                $partnerData,
                $platformCoupons[$partnerEmail] ?? [],
                $month,
                $year
            );

            DB::commit();

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[StripeCommissionInvoicing] Errore generazione fattura singola: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Raggruppa commissioni per partner (email)
     */
    private function groupFeesByPartner(array $fees): array
    {
        $grouped = [];

        foreach ($fees as $fee) {
            $email = $fee['partner_email'] ?? 'unknown';

            if (!isset($grouped[$email])) {
                $grouped[$email] = [
                    'partner_email' => $email,
                    'partner_name' => $fee['partner_name'] ?? 'N/A',
                    'partner_id' => $fee['partner_id'] ?? null,
                    'fees' => [],
                    'total_amount' => 0,
                    'transaction_count' => 0,
                ];
            }

            // Se partner_id è null ma questa fee ce l'ha, aggiorna
            if (empty($grouped[$email]['partner_id']) && !empty($fee['partner_id'])) {
                $grouped[$email]['partner_id'] = $fee['partner_id'];
            }

            $grouped[$email]['fees'][] = $fee;
            $grouped[$email]['total_amount'] += $fee['amount'];
            $grouped[$email]['transaction_count']++;
        }

        return $grouped;
    }

    /**
     * Recupera coupon piattaforma dal database OPPLA Admin
     * Filtra solo quelli con "has_platform_discount" = true
     */
    private function getPlatformCoupons(int $month, int $year): array
    {
        try {
            // NOTA: Il database esterno non ha le colonne necessarie
            // Implementazione futura quando il DB esterno sarà aggiornato
            // Per ora restituisce array vuoto
            Log::info('[StripeCommissionInvoicing] getPlatformCoupons - Database esterno non supportato', [
                'month' => $month,
                'year' => $year,
            ]);

            return [];

            /* Codice originale - da riattivare quando DB esterno sarà aggiornato:
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Query al database esterno PostgreSQL (OPPLA Admin)
            $coupons = DB::connection('oppla_readonly')
                ->table('orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('has_platform_discount', true)
                ->select([
                    'id as order_id',
                    'restaurant_email',
                    'platform_discount_amount',
                    'platform_discount_code',
                    'stripe_coupon_id', // fee_XXXXXXX
                    'created_at',
                ])
                ->get();
            */

            /* Codice originale - raggruppa per email ristorante:
            $grouped = [];

            foreach ($coupons as $coupon) {
                $email = $coupon->restaurant_email;
                
                if (!isset($grouped[$email])) {
                    $grouped[$email] = [];
                }

                $grouped[$email][] = [
                    'order_id' => $coupon->order_id,
                    'amount' => abs((float) $coupon->platform_discount_amount), // Valore positivo
                    'code' => $coupon->platform_discount_code,
                    'stripe_coupon_id' => $coupon->stripe_coupon_id,
                    'date' => Carbon::parse($coupon->created_at),
                ];
            }

            Log::info('[StripeCommissionInvoicing] Coupon piattaforma recuperati', [
                'total_coupons' => $coupons->count(),
                'partners_with_coupons' => count($grouped),
            ]);

            return $grouped;
            */

        } catch (\Exception $e) {
            Log::warning('[StripeCommissionInvoicing] Impossibile recuperare coupon piattaforma: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trova o crea cliente per partner Stripe
     */
    private function findOrCreateClientForPartner(string $email, array $partnerData): ?Client
    {
        // 1. Cerca per email diretta del cliente
        $client = Client::where('email', $email)->first();

        if ($client) {
            return $client;
        }

        // 2. Cerca per ID partner esterno (dal database ApplicationFee)
        if (!empty($partnerData['partner_id'])) {
            $client = Client::find($partnerData['partner_id']);

            if ($client) {
                return $client;
            }
        }

        // 3. Cerca client_id da tutte le fee del gruppo (potrebbe essere diverso dalla prima)
        if (!empty($partnerData['fees'])) {
            foreach ($partnerData['fees'] as $fee) {
                if (!empty($fee['partner_id'])) {
                    $client = Client::find($fee['partner_id']);
                    if ($client) {
                        return $client;
                    }
                }
            }
        }

        // 4. Cerca per ragione sociale (matching parziale, qualsiasi tipo)
        if (!empty($partnerData['partner_name']) && $partnerData['partner_name'] !== 'N/A' && $partnerData['partner_name'] !== 'Partner Sconosciuto') {
            $client = Client::where('ragione_sociale', 'LIKE', '%' . $partnerData['partner_name'] . '%')
                ->first();

            if ($client) {
                return $client;
            }
        }

        Log::warning('[StripeCommissionInvoicing] Cliente non trovato per partner', [
            'email' => $email,
            'name' => $partnerData['partner_name'],
            'partner_id' => $partnerData['partner_id'],
        ]);

        return null;
    }

    /**
     * Valida e normalizza P.IVA e Codice Fiscale per società
     * Per le società italiane, P.IVA e CF devono coincidere
     * 
     * @param Client $client
     * @return void
     */
    private function validateAndFixVatTaxCode(Client $client): void
    {
        // Se mancano entrambi, non possiamo fare nulla
        if (empty($client->piva) && empty($client->codice_fiscale)) {
            Log::warning('[StripeCommissionInvoicing] Cliente senza P.IVA e CF', ['client_id' => $client->id]);
            return;
        }

        // Se è una società (ha P.IVA), CF deve essere uguale a P.IVA
        if (!empty($client->piva)) {
            // Se CF è vuoto o diverso dalla P.IVA, lo correggiamo
            if (empty($client->codice_fiscale) || $client->codice_fiscale !== $client->piva) {
                Log::info('[StripeCommissionInvoicing] Normalizzazione CF per società', [
                    'client_id' => $client->id,
                    'piva' => $client->piva,
                    'old_codice_fiscale' => $client->codice_fiscale,
                    'action' => 'CF impostato uguale a P.IVA'
                ]);

                $client->codice_fiscale = $client->piva;
                $client->save();
            }
        }
        // Se ha solo CF (persona fisica), lo lasciamo com'è
    }

    /**
     * Genera singola fattura per un partner specifico
     */
    private function generatePartnerCommissionInvoice(
        Client $client,
        array $partnerData,
        array $platformCoupons,
        int $month,
        int $year
    ): ?Invoice {
        // Controlla se esiste già una fattura differita per questo cliente/periodo
        $existingInvoice = Invoice::withoutTrashed()
            ->where('client_id', $client->id)
            ->where('type', 'attiva')
            ->where('invoice_type', 'differita')
            ->where('causale', "Commissioni Stripe - Periodo {$month}/{$year}")
            ->first();

        if ($existingInvoice) {
            Log::info('[StripeCommissionInvoicing] Fattura già esistente per questo partner/periodo, skip', [
                'client_id' => $client->id,
                'invoice_id' => $existingInvoice->id,
                'periodo' => "{$month}/{$year}",
            ]);
            return null;
        }

        // Valida P.IVA e CF del cliente
        $this->validateAndFixVatTaxCode($client);
        
        $totalCommissions = $partnerData['total_amount'];
        $totalCoupons = array_sum(array_column($platformCoupons, 'amount'));
        
        // Importo netto da fatturare (commissioni - coupon)
        $netAmount = $totalCommissions - $totalCoupons;

        if ($netAmount <= 0) {
            Log::info('[StripeCommissionInvoicing] Importo netto nullo o negativo, fattura non generata', [
                'client_id' => $client->id,
                'commissions' => $totalCommissions,
                'coupons' => $totalCoupons,
            ]);
            return null;
        }

        // Le commissioni Stripe sono GIÀ IVA INCLUSA - dobbiamo SCORPORARE il 22%
        $totale = $netAmount; // Importo lordo (IVA inclusa)
        $imponibile = $totale / 1.22; // Scorporo IVA
        $iva = $totale - $imponibile; // IVA = differenza

        // Crea dettaglio transazioni per campo note (senza email, con a capo)
        $dettaglioNote = "Dettaglio transazioni:\n";
        
        foreach ($partnerData['fees'] as $fee) {
            $date = Carbon::parse($fee['created'])->format('d/m/Y H:i');
            $amount = number_format($fee['amount'], 2, ',', '.');
            // Estrai solo l'account ID dalla descrizione (rimuovi email)
            $description = $fee['description'];
            // Se la descrizione contiene " - acct_", prendi solo la parte dopo
            if (strpos($description, ' - acct_') !== false) {
                $parts = explode(' - acct_', $description);
                $accountId = 'acct_' . $parts[1];
            } else {
                $accountId = $description;
            }
            $dettaglioNote .= "{$date}: €{$amount} - {$accountId}\n";
        }
        
        // Aggiungi info coupon se presenti
        if (!empty($platformCoupons)) {
            $dettaglioNote .= "\nCoupon piattaforma applicati: " . count($platformCoupons) . "\n";
            foreach ($platformCoupons as $coupon) {
                $couponDate = Carbon::parse($coupon['date'])->format('d/m/Y');
                $couponAmount = number_format($coupon['amount'], 2, ',', '.');
                $dettaglioNote .= "{$couponDate}: -€{$couponAmount} ({$coupon['code']})\n";
            }
        }
        
        $dettaglioNote .= "\nFattura differita commissioni Stripe - Periodo: {$month}/{$year}";
        $dettaglioNote .= "\nAi sensi dell'art. 21 Dpr 633/72. L'originale è disponibile all'indirizzo telematico da Lei fornito oppure nella Sua area riservata.";

        // Usa transazione per evitare race condition sul numero fattura
        return DB::transaction(function () use ($client, $partnerData, $platformCoupons, $month, $year, $imponibile, $iva, $totale, $totalCommissions, $totalCoupons, $netAmount, $dettaglioNote) {
            // Crea fattura differita in locale (senza numero fattura ancora)
            // Il numero viene generato dopo, all'interno della transazione con lock
            // IMPORTANTE: anno viene sovrascritto da generateInvoiceNumber() con anno corrente
            // Data emissione: ultimo giorno del mese delle commissioni (non mese corrente!)
            $emissionDate = Carbon::create($year, $month, 1)->endOfMonth();
            $dueDate = Carbon::create($year, $month, 1)->endOfMonth()->addDays(30);
            
            $invoice = new Invoice([
                'client_id' => $client->id,
                'type' => 'attiva',
                'invoice_type' => 'differita', // Fattura differita (NON 'deferred')
                'anno' => null, // Sarà impostato da generateInvoiceNumber() con anno corrente (2026)
                'data_emissione' => $emissionDate, // Fine mese del periodo commissioni (es. 31/12/2025)
                'data_scadenza' => $dueDate, // +30gg dalla data emissione
                'imponibile' => round($imponibile, 2),
                'iva' => round($iva, 2),
                'totale' => round($totale, 2),
                'status' => 'bozza', // Bozza, da consolidare e inviare manualmente
                'payment_status' => 'non_pagata', // NON 'pending'
                'payment_method' => 'bonifico',
                'note' => $dettaglioNote,
                'causale' => "Commissioni Stripe - Periodo {$month}/{$year}",
            ]);
            
            // Genera numero fattura DENTRO la transazione con lock
            $invoice->generateInvoiceNumber();
            $invoice->save();

        // Descrizione breve per l'item (SOLO riepilogo senza dettagli)
        $descrizione = "Commissioni Stripe - Periodo {$month}/{$year} - {$partnerData['transaction_count']} transazioni";

        // Unica voce con importo netto e descrizione riepilogativa
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'descrizione' => $descrizione,
            'quantita' => 1,
            'prezzo_unitario' => round($imponibile, 2),
            'iva_percentuale' => 22,
            'subtotale' => round($imponibile, 2),
        ]);

        Log::info('[StripeCommissionInvoicing] Fattura generata', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->numero_fattura,
            'client_id' => $client->id,
            'total' => $netAmount,
            'commissions' => $totalCommissions,
            'coupons' => $totalCoupons,
        ]);

        return $invoice;
        }); // Fine transazione DB
    }

    /**
     * Pre-genera fatture in formato JSON per revisione manuale
     * Prima di inviarle a Fatture in Cloud
     */
    public function pregenerateCommissionInvoices(int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Recupera commissioni dal database locale (ApplicationFee)
        $periodMonth = sprintf('%04d-%02d', $year, $month);
        
        Log::info('[PregenerateCommissionInvoices] Inizio pre-generazione', [
            'period_month' => $periodMonth,
            'month' => $month,
            'year' => $year,
        ]);
        
        $fees = ApplicationFee::where('period_month', $periodMonth)
            ->orderBy('created_at_stripe', 'asc')
            ->get()
            ->map(function ($fee) {
                return [
                    'id' => $fee->stripe_fee_id,
                    'amount' => $fee->amount,
                    'currency' => $fee->currency,
                    'created' => $fee->created_at_stripe,
                    'partner_email' => $fee->partner_email,
                    'partner_name' => $fee->partner_name,
                    'partner_id' => $fee->client_id,
                    'charge_id' => $fee->charge_id,
                    'description' => $fee->description,
                ];
            })
            ->toArray();
            
        Log::info('[PregenerateCommissionInvoices] Commissioni trovate', [
            'count' => count($fees),
            'period_month' => $periodMonth,
        ]);
        
        // DEBUG: Se non troviamo commissioni, vediamo cosa c'è nel database
        if (empty($fees)) {
            $allPeriods = ApplicationFee::select('period_month')
                ->distinct()
                ->pluck('period_month')
                ->toArray();
            
            $totalCount = ApplicationFee::count();
            
            Log::warning('[PregenerateCommissionInvoices] Nessuna commissione per periodo', [
                'period_searched' => $periodMonth,
                'periods_available' => $allPeriods,
                'total_application_fees' => $totalCount,
            ]);
        }
            
        $platformCoupons = $this->getPlatformCoupons($month, $year);
        $grouped = $this->groupFeesByPartner($fees);

        $previews = [];
        
        // Se non ci sono commissioni, restituisci array vuoto con messaggio di debug
        if (empty($fees)) {
            Log::info('[PregenerateCommissionInvoices] Nessuna commissione da fatturare', [
                'period_month' => $periodMonth,
                'message' => 'Verifica che le commissioni Stripe siano state sincronizzate per questo periodo',
            ]);
            
            return [];
        }

        foreach ($grouped as $partnerEmail => $partnerData) {
            $client = $this->findOrCreateClientForPartner($partnerEmail, $partnerData);

            if (!$client) {
                $previews[] = [
                    'partner_email' => $partnerEmail,
                    'partner_name' => $partnerData['partner_name'],
                    'error' => 'Cliente non trovato - Associare manualmente il partner',
                    'total_commissions' => $partnerData['total_amount'],
                    'transaction_count' => $partnerData['transaction_count'],
                    'invoice_ready' => false,
                ];
                continue;
            }

            $coupons = $platformCoupons[$partnerEmail] ?? [];
            $totalCommissions = $partnerData['total_amount'];
            $totalCoupons = array_sum(array_column($coupons, 'amount'));
            $netAmount = $totalCommissions - $totalCoupons;

            // Verifica se esiste già una fattura per questo cliente e periodo
            $existingInvoice = Invoice::where('client_id', $client->id)
                ->where('invoice_type', 'differita')
                ->whereYear('data_emissione', $year)
                ->whereMonth('data_emissione', $month)
                ->where('causale', 'LIKE', "Commissioni Stripe - Periodo {$month}/{$year}%")
                ->first();

            $previews[] = [
                'partner_email' => $partnerEmail,
                'partner_name' => $partnerData['partner_name'],
                'client_id' => $client->id,
                'client_name' => $client->ragione_sociale,
                'total_commissions' => $totalCommissions,
                'total_coupons' => $totalCoupons,
                'net_amount' => $netAmount,
                'transaction_count' => $partnerData['transaction_count'],
                'coupon_count' => count($coupons),
                'invoice_ready' => $netAmount > 0 && !$existingInvoice,
                'already_generated' => $existingInvoice !== null,
                'existing_invoice_id' => $existingInvoice?->id,
                'existing_invoice_number' => $existingInvoice?->numero_fattura,
                'transactions' => array_map(function ($fee) {
                    return [
                        'id' => $fee['id'],
                        'amount' => $fee['amount'],
                        'charge_id' => $fee['charge_id'],
                        'description' => $fee['description'],
                        'created' => $fee['created']->format('d/m/Y H:i'),
                    ];
                }, $partnerData['fees']),
                'coupons' => $coupons,
            ];
        }

        return $previews;
    }

    /**
     * Invia fatture differite a Fatture in Cloud
     */
    public function sendDeferredInvoicesToFIC(int $month, int $year): array
    {
        $invoices = Invoice::where('invoice_type', 'differita')
            ->whereYear('data_emissione', $year)
            ->whereMonth('data_emissione', $month)
            ->where('status', 'bozza')
            ->get();

        $results = [];

        foreach ($invoices as $invoice) {
            try {
                // Invia a Fatture in Cloud
                $ficResult = $this->ficService->createInvoice($invoice);

                // Aggiorna stato fattura
                $invoice->update([
                    'status' => 'emessa',
                    'fic_document_id' => $ficResult['id'] ?? null,
                ]);

                $results[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->numero_fattura,
                    'client_name' => $invoice->client->ragione_sociale,
                    'success' => true,
                    'fic_document_id' => $ficResult['id'] ?? null,
                ];

                Log::info('[StripeCommissionInvoicing] Fattura inviata a FIC', [
                    'invoice_id' => $invoice->id,
                    'fic_id' => $ficResult['id'] ?? null,
                ]);

            } catch (\Exception $e) {
                $results[] = [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->numero_fattura,
                    'client_name' => $invoice->client->ragione_sociale,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                Log::error('[StripeCommissionInvoicing] Errore invio FIC', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
