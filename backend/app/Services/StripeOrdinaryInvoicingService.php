<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Service per gestire la fatturazione ordinaria Stripe
 * 
 * Genera fatture ordinarie mensili importando le invoice Stripe dal 26 del mese precedente al 6 del mese corrente (±5 giorni dal 1°)
 * Le fatture vengono datate il 1° del mese successivo al periodo di riferimento
 * 
 * Workflow:
 * 1. Importa invoice Stripe tramite API dal 26/MM-1 al 06/MM (±5 giorni dal 1°)
 * 2. Raggruppa per email cliente (Stripe Customer)
 * 3. Esclude invoice draft e con importo 0€
 * 4. Genera fattura con descrizione "Tariffe mensile [mese] [anno]"
 * 5. Dettaglio invoice nelle note
 * 6. Data fattura: 1° del mese successivo
 * 7. Invia a Fatture in Cloud via FattureInCloudService
 */
class StripeOrdinaryInvoicingService
{
    protected $ficService;
    protected StripeClient $stripe;

    public function __construct(FattureInCloudService $ficService)
    {
        $this->ficService = $ficService;
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Pre-genera anteprima fatture ordinarie (senza salvarle in DB)
     * Importa invoice Stripe tramite API per tutto il mese selezionato
     * 
     * @param int $year Anno di riferimento
     * @param int $month Mese di riferimento (1-12)
     * @return array Array di preview fatture
     */
    public function pregenerateOrdinaryInvoices(int $year, int $month): array
    {
        // Calcola date periodo: dal 26 del mese precedente al 6 del mese corrente (±5 giorni dal 1°)
        $firstOfMonth = Carbon::create($year, $month, 1, 0, 0, 0);
        $startDate = $firstOfMonth->copy()->subDays(5); // 26 del mese precedente
        $endDate = $firstOfMonth->copy()->addDays(5)->endOfDay(); // 6 del mese corrente alle 23:59

        Log::info("Pre-generazione fatture ordinarie Stripe da API", [
            'period_start' => $startDate->toDateTimeString(),
            'period_end' => $endDate->toDateTimeString(),
            'start_timestamp' => $startDate->timestamp,
            'end_timestamp' => $endDate->timestamp
        ]);

        try {
            // Importa invoice Stripe tramite API nel periodo
            $stripeInvoices = $this->stripe->invoices->all([
                'created' => [
                    'gte' => $startDate->timestamp,
                    'lte' => $endDate->timestamp,
                ],
                'limit' => 100, // Stripe paginera automaticamente
            ]);

            Log::info("Invoice Stripe ricevute da API", [
                'count' => count($stripeInvoices->data),
                'has_more' => $stripeInvoices->has_more
            ]);

            // Converti in array per processamento
            $invoices = [];
            foreach ($stripeInvoices->autoPagingIterator() as $invoice) {
                // Escludi draft e invoice con importo 0€
                if ($invoice->status === 'draft' || $invoice->total == 0) {
                    Log::debug("Invoice esclusa", [
                        'id' => $invoice->id,
                        'status' => $invoice->status,
                        'total' => $invoice->total
                    ]);
                    continue;
                }

                // FILTRO RIMOSSO: prendi tutte le invoice del mese (non solo quelle del 1°)
                $invoiceDate = Carbon::createFromTimestamp($invoice->created);

                // Recupera dati cliente
                $customerEmail = null;
                $customerName = null;
                
                if ($invoice->customer) {
                    try {
                        $customer = $this->stripe->customers->retrieve($invoice->customer);
                        $customerEmail = $customer->email;
                        $customerName = $customer->name ?? $customer->description;
                    } catch (\Exception $e) {
                        Log::warning("Impossibile recuperare customer", [
                            'customer_id' => $invoice->customer,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $invoices[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'customer_id' => $invoice->customer,
                    'customer_email' => $customerEmail,
                    'customer_name' => $customerName,
                    'amount_due' => $invoice->amount_due / 100, // Converti da centesimi
                    'amount_paid' => $invoice->amount_paid / 100,
                    'subtotal' => $invoice->subtotal / 100,
                    'total' => $invoice->total / 100,
                    'tax' => isset($invoice->tax) ? ($invoice->tax / 100) : 0, // Tax può essere null o undefined
                    'status' => $invoice->status,
                    'created' => $invoice->created,
                    'date' => date('Y-m-d H:i:s', $invoice->created),
                    'currency' => strtoupper($invoice->currency),
                ];
            }

            Log::info("Invoice Stripe processate", [
                'total_filtered' => count($invoices),
                'sample' => array_slice($invoices, 0, 3)
            ]);

            // Raggruppa per email cliente
            $groupedByCustomer = collect($invoices)->groupBy('customer_email');

            $previews = [];

            foreach ($groupedByCustomer as $customerEmail => $customerInvoices) {
                if (empty($customerEmail)) {
                    Log::warning("Invoice senza email cliente", [
                        'count' => $customerInvoices->count(),
                        'invoice_ids' => $customerInvoices->pluck('id')->toArray()
                    ]);
                    continue;
                }

                // Cerca cliente nel database locale
                $client = $this->findClientByEmail($customerEmail);
                
                Log::info("Ricerca cliente per invoice Stripe", [
                    'customer_email' => $customerEmail,
                    'customer_name' => $customerInvoices->first()['customer_name'] ?? null,
                    'found_by_email' => $client ? true : false
                ]);
                
                // Fallback: cerca per nome cliente (più flessibile)
                if (!$client && !empty($customerInvoices->first()['customer_name'])) {
                    $customerName = $customerInvoices->first()['customer_name'];
                    
                    // Prova ricerca esatta
                    $client = Client::where('ragione_sociale', 'LIKE', '%' . $customerName . '%')
                        ->first();
                    
                    // Se non trovato, prova con parti del nome (es. "Sushino" da "Sushino Experience")
                    if (!$client) {
                        $nameParts = explode(' ', $customerName);
                        foreach ($nameParts as $part) {
                            if (strlen($part) >= 4) { // Solo parole significative
                                $client = Client::where('ragione_sociale', 'LIKE', '%' . $part . '%')
                                    ->first();
                                if ($client) {
                                    Log::info("Cliente trovato per parte del nome", [
                                        'search_term' => $part,
                                        'customer_name' => $customerName,
                                        'client_id' => $client->id,
                                        'client_ragione_sociale' => $client->ragione_sociale
                                    ]);
                                    break;
                                }
                            }
                        }
                    }
                    
                    if ($client) {
                        Log::info("Cliente trovato per nome", [
                            'customer_name' => $customerName,
                            'client_id' => $client->id,
                            'client_ragione_sociale' => $client->ragione_sociale
                        ]);
                    }
                }

                // Validazione IMMEDIATA P.IVA/CF se cliente trovato
                $validationWarning = null;
                if ($client) {
                    if (empty($client->partita_iva) && empty($client->codice_fiscale)) {
                        $validationWarning = 'Cliente senza P.IVA e Codice Fiscale - la fattura risulterà DA COMPLETARE su FIC';
                        Log::warning('Cliente trovato ma senza dati fiscali', [
                            'client_id' => $client->id,
                            'ragione_sociale' => $client->ragione_sociale,
                            'email' => $customerEmail
                        ]);
                    }
                }

                // Nome partner
                $partnerName = $customerInvoices->first()['customer_name'] ?? $customerEmail;
                if ($client && !$customerInvoices->first()['customer_name']) {
                    $partnerName = $client->ragione_sociale;
                }

                // Calcola totale
                $totalAmount = $customerInvoices->sum('total');

                // Verifica se fattura già esiste
                $existingInvoice = $this->findExistingOrdinaryInvoice($year, $month, $customerEmail);

                $preview = [
                    'partner_email' => $customerEmail,
                    'partner_name' => $partnerName,
                    'client_id' => $client?->id,
                    'client_name' => $client?->ragione_sociale,
                    'validation_warning' => $validationWarning, // NUOVO: warning se mancano dati fiscali
                    'transaction_count' => $customerInvoices->count(),
                    'total_amount' => abs($totalAmount),
                    'sample_transactions' => $customerInvoices->map(function ($inv) { // MODIFICATO: tutte le transazioni, non solo 5
                        return [
                            'id' => $inv['number'] ?? $inv['id'],
                            'date' => $inv['date'],
                            'description' => "Invoice {$inv['number']} - Status: {$inv['status']}",
                            'amount' => $inv['total'],
                            'type' => 'stripe_invoice'
                        ];
                    })->toArray(),
                    'invoice_id' => $existingInvoice?->id,
                    'invoice_number' => $existingInvoice?->invoice_number,
                    'fic_sent' => $existingInvoice?->fic_document_id ? true : false
                ];

                $previews[] = $preview;
            }

            // Ordina per importo decrescente
            usort($previews, function ($a, $b) {
                return $b['total_amount'] <=> $a['total_amount'];
            });

            Log::info("Preview fatture ordinarie generata da API Stripe", [
                'total_invoices' => count($previews),
                'total_stripe_invoices' => count($invoices)
            ]);

            return $previews;

        } catch (\Exception $e) {
            Log::error("Errore importazione invoice Stripe", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception("Errore importazione invoice Stripe: " . $e->getMessage());
        }
    }

    /**
     * Genera fatture ordinarie per tutti i partner nel periodo
     * SERIALIZZATO per evitare race condition sui numeri fattura
     * 
     * @param int $year
     * @param int $month
     * @return array Risultati generazione
     */
    public function generateOrdinaryInvoices(int $year, int $month): array
    {
        $previews = $this->pregenerateOrdinaryInvoices($year, $month);
        
        $invoicesCreated = 0;
        $errors = [];

        // Usa un lock applicativo per serializzare la generazione
        // Questo previene completamente le race condition
        $lockKey = "stripe_ordinary_invoicing_{$year}_{$month}";
        
        // Usa cache lock (supporta Redis, Memcached, Database)
        $lock = \Cache::lock($lockKey, 300); // 5 minuti timeout
        
        try {
            // Blocca e attendi fino a 10 secondi per acquisire il lock
            if (!$lock->block(10)) {
                throw new \Exception("Impossibile acquisire il lock per la generazione fatture. Un altro processo potrebbe essere in esecuzione.");
            }
            
            // Ora siamo sicuri che un solo processo alla volta genera fatture
            foreach ($previews as $preview) {
                // Salta se fattura già esistente
                if ($preview['invoice_id']) {
                    Log::info("Fattura già esistente per partner", [
                        'partner_email' => $preview['partner_email'],
                        'invoice_id' => $preview['invoice_id']
                    ]);
                    continue;
                }

                // Salta se cliente non trovato
                if (!$preview['client_id']) {
                    Log::warning("Cliente non trovato per partner", [
                        'partner_email' => $preview['partner_email']
                    ]);
                    $errors[] = "Cliente non trovato per {$preview['partner_email']}";
                    continue;
                }

                try {
                    // Ogni fattura in una transaction separata con lock per il numero
                    $invoice = $this->createOrdinaryInvoice($year, $month, $preview);
                    $invoicesCreated++;
                    
                    Log::info("Fattura ordinaria creata", [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->numero_fattura,
                        'client_id' => $invoice->client_id
                    ]);
                } catch (\Exception $e) {
                    Log::error("Errore creazione fattura ordinaria", [
                        'partner_email' => $preview['partner_email'],
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = "Errore per {$preview['partner_email']}: {$e->getMessage()}";
                }
            }
        } finally {
            // Rilascia sempre il lock
            $lock->release();
        }

        return [
            'invoices_created' => $invoicesCreated,
            'errors' => $errors,
            'total_previews' => count($previews)
        ];
    }

    /**
     * Genera fattura ordinaria per singolo partner
     * 
     * @param int $year
     * @param int $month
     * @param string $partnerEmail
     * @return Invoice
     */
    public function generateSingleOrdinaryInvoice(int $year, int $month, string $partnerEmail): Invoice
    {
        $previews = $this->pregenerateOrdinaryInvoices($year, $month);
        
        $preview = collect($previews)->firstWhere('partner_email', $partnerEmail);

        if (!$preview) {
            throw new \Exception("Nessuna transazione trovata per $partnerEmail nel periodo");
        }

        if ($preview['invoice_id']) {
            throw new \Exception("Fattura già esistente per $partnerEmail");
        }

        if (!$preview['client_id']) {
            throw new \Exception("Cliente non trovato per $partnerEmail");
        }

        DB::beginTransaction();
        try {
            $invoice = $this->createOrdinaryInvoice($year, $month, $preview);
            DB::commit();

            Log::info("Fattura ordinaria singola creata", [
                'invoice_id' => $invoice->id,
                'partner_email' => $partnerEmail
            ]);

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore generazione fattura ordinaria singola", [
                'partner_email' => $partnerEmail,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Invia fatture ordinarie generate a Fatture in Cloud
     * 
     * @param int $year
     * @param int $month
     * @return array Risultati invio
     */
    public function sendOrdinaryInvoicesToFIC(int $year, int $month): array
    {
        // Trova fatture ordinarie del periodo non ancora inviate a FIC
        $invoiceDate = Carbon::create($year, $month, 1)->addMonth();
        
        $invoices = Invoice::where('data_emissione', $invoiceDate->toDateString())
            ->where('type', 'attiva')
            ->where('invoice_type', 'ordinaria')
            ->where('causale', 'LIKE', '%Tariffe mensile%')
            ->whereNull('fic_document_id')
            ->get();

        Log::info("Invio fatture ordinarie a FIC", [
            'count' => $invoices->count(),
            'invoice_date' => $invoiceDate->toDateString()
        ]);

        $sentCount = 0;
        $errors = [];

        foreach ($invoices as $invoice) {
            try {
                $this->ficService->sendInvoiceToFIC($invoice);
                $sentCount++;
                
                Log::info("Fattura ordinaria inviata a FIC", [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id
                ]);
            } catch (\Exception $e) {
                Log::error("Errore invio fattura ordinaria a FIC", [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
                $errors[] = "Fattura #{$invoice->numero_fattura}: {$e->getMessage()}";
            }
        }

        return [
            'sent_count' => $sentCount,
            'total_invoices' => $invoices->count(),
            'errors' => $errors
        ];
    }

    /**
     * Valida e normalizza P.IVA e Codice Fiscale per società
     * Per le società italiane, P.IVA e CF devono coincidere
     * 
     * @param Client $client
     * @return void
     */
    protected function validateAndFixVatTaxCode(Client $client): void
    {
        // Se mancano entrambi, non possiamo fare nulla
        if (empty($client->vat_number) && empty($client->codice_fiscale)) {
            Log::warning('Cliente senza P.IVA e CF', ['client_id' => $client->id]);
            return;
        }

        // Se è una società (ha P.IVA), CF deve essere uguale a P.IVA
        if (!empty($client->vat_number)) {
            // Se CF è vuoto o diverso dalla P.IVA, lo correggiamo
            if (empty($client->codice_fiscale) || $client->codice_fiscale !== $client->vat_number) {
                Log::info('Normalizzazione CF per società', [
                    'client_id' => $client->id,
                    'vat_number' => $client->vat_number,
                    'old_codice_fiscale' => $client->codice_fiscale,
                    'action' => 'CF impostato uguale a P.IVA'
                ]);
                
                $client->codice_fiscale = $client->vat_number;
                $client->save();
            }
        }
        // Se ha solo CF (persona fisica), lo lasciamo com'è
    }

    /**
     * Crea fattura ordinaria nel database
     * 
     * @param int $year
     * @param int $month
     * @param array $preview
     * @return Invoice
     */
    protected function createOrdinaryInvoice(int $year, int $month, array $preview): Invoice
    {
        // Usa transazione per evitare race condition sul numero fattura
        // Ogni fattura DEVE essere creata in una transaction separata con lock
        return DB::transaction(function () use ($year, $month, $preview) {
            // Valida P.IVA e CF del cliente
            $client = Client::findOrFail($preview['client_id']);
            $this->validateAndFixVatTaxCode($client);
            
            // Data fattura: 1° del mese selezionato
            $invoiceDate = Carbon::create($year, $month, 1);
            
            // Descrizione: "Tariffe mensile [mese] [anno]"
            $monthName = $invoiceDate->locale('it')->translatedFormat('F Y');
            $description = "Tariffe mensile {$monthName}";

            // Prepara note con dettaglio transazioni
            $notes = $this->buildInvoiceNotes($year, $month, $preview);

            // Calcolo IVA: le transazioni Stripe includono già IVA, quindi scorporiamo
            $totale = abs($preview['total_amount']); // Importo lordo (IVA inclusa)
            $imponibile = $totale / 1.22; // Scorporo IVA 22%
            $iva = $totale - $imponibile; // IVA = differenza

            // Crea fattura ordinaria
            $invoice = new Invoice([
                'client_id' => $preview['client_id'],
                'type' => 'attiva',
                'invoice_type' => 'ordinaria',
                'anno' => null, // Sarà impostato da generateInvoiceNumber()
                'data_emissione' => $invoiceDate->toDateString(),
                'data_scadenza' => $invoiceDate->copy()->addDays(30)->toDateString(),
                'imponibile' => round($imponibile, 2),
                'iva' => round($iva, 2),
                'totale' => round($totale, 2),
                'status' => 'bozza',
                'payment_status' => 'non_pagata',
                'payment_method' => 'bonifico',
                'note' => $notes,
                'causale' => $description,
            ]);

            // Genera numero fattura DENTRO la transazione con lock
            $invoice->generateInvoiceNumber();
            $invoice->save();

            // Crea item fattura con descrizione breve
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'descrizione' => $description,
                'quantita' => 1,
                'prezzo_unitario' => round($imponibile, 2),
                'iva_percentuale' => 22,
                'subtotale' => round($imponibile, 2),
            ]);

            Log::info('Fattura ordinaria Stripe creata', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->numero_fattura,
                'client_id' => $invoice->client_id,
                'period' => "{$month}/{$year}"
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Costruisce note fattura con dettaglio transazioni
     * TUTTE le transazioni devono essere elencate per fatture differite TD24
     *
     * @param int $year
     * @param int $month
     * @param array $preview
     * @return string
     */
    protected function buildInvoiceNotes(int $year, int $month, array $preview): string
    {
        $startDate = Carbon::create($year, $month, 20);
        $endDate = Carbon::create($year, $month, 1)->addMonth();

        $notes = "Periodo: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}\n\n";
        $notes .= "Dettaglio transazioni ({$preview['transaction_count']}):\n";
        $notes .= str_repeat('-', 50) . "\n";

        // MODIFICATO: Include TUTTE le transazioni, non solo le prime 5
        foreach ($preview['sample_transactions'] as $tx) {
            $txDate = Carbon::parse($tx['date'])->format('d/m/Y');
            $amount = number_format(abs($tx['amount']), 2, ',', '.') . ' €';
            $notes .= "{$txDate} - {$tx['description']} - {$amount}\n";
        }

        $notes .= str_repeat('-', 50) . "\n";
        $notes .= "Totale: " . number_format(abs($preview['total_amount']), 2, ',', '.') . " €";

        return $notes;
    }

    /**
     * Trova cliente via email (normalizzata)
     * 
     * @param string $email
     * @return Client|null
     */
    protected function findClientByEmail(string $email): ?Client
    {
        $email = strtolower(trim($email));
        
        // Cerca email esatta
        $client = Client::where('email', $email)->first();
        
        // Fallback: cerca per dominio email (es. @sushinoexperience.com)
        if (!$client && strpos($email, '@') !== false) {
            $domain = substr($email, strpos($email, '@'));
            $client = Client::where('email', 'LIKE', '%' . $domain)->first();
            
            if ($client) {
                Log::info("Cliente trovato per dominio email", [
                    'search_email' => $email,
                    'found_email' => $client->email,
                    'client_id' => $client->id
                ]);
            }
        }
        
        return $client;
    }

    /**
     * Estrae nome partner dalla descrizione Stripe
     * Esempi:
     * - "Application fee from application Oppla SRL for alepizza2000@gmail.com"
     * - "Application fee from Pizzeria Da Mario for email@example.com"
     * 
     * @param string $description
     * @return string|null
     */
    protected function extractPartnerNameFromDescription(string $description): ?string
    {
        // Pattern: "from application NOME for EMAIL" o "from NOME for EMAIL"
        if (preg_match('/from\s+(?:application\s+)?(.+?)\s+for\s+[^\s]+@/i', $description, $matches)) {
            $name = trim($matches[1]);
            // Rimuovi "application" se ancora presente
            $name = preg_replace('/^application\s+/i', '', $name);
            return $name;
        }
        
        // Pattern alternativo: "from NOME - acct_"
        if (preg_match('/from\s+(?:application\s+)?(.+?)\s*-\s*acct_/i', $description, $matches)) {
            $name = trim($matches[1]);
            $name = preg_replace('/^application\s+/i', '', $name);
            return $name;
        }
        
        return null;
    }

    /**
     * Cerca fattura ordinaria esistente per periodo e partner
     * 
     * @param int $year
     * @param int $month
     * @param string $partnerEmail
     * @return Invoice|null
     */
    protected function findExistingOrdinaryInvoice(int $year, int $month, string $partnerEmail): ?Invoice
    {
        $invoiceDate = Carbon::create($year, $month, 1)->addMonth();
        
        // Cerca fattura ordinaria Stripe con metadata partner_email
        return Invoice::where('data_emissione', $invoiceDate->toDateString())
            ->where('type', 'attiva')
            ->where('invoice_type', 'ordinaria')
            ->where('causale', 'LIKE', '%Tariffe mensile%')
            ->whereHas('client', function ($query) use ($partnerEmail) {
                $query->where('email', $partnerEmail);
            })
            ->first();
    }

    /**
     * Estrae email da stringa descrizione (fallback)
     * 
     * @param string $description
     * @return string|null
     */
    protected function extractEmailFromDescription(string $description): ?string
    {
        // Pattern regex per email
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $description, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Indovina nome partner da email (fallback)
     * 
     * @param string $email
     * @return string
     */
    protected function guessPartnerName(string $email): string
    {
        $parts = explode('@', $email);
        return ucfirst($parts[0]) . ' (Partner)';
    }
}
