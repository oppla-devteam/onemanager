<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Delivery;
use App\Models\PosOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Services\FattureInCloudService;

class InvoicingService
{
    /**
     * Genera fatture differite per tutti i partner OPPLA
     * Eseguito il 1° del mese per il mese precedente
     */
    public function generateMonthlyInvoices(int $year, int $month): array
    {
        $results = [];
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $partners = Client::partnerOppla()
            ->active()
            ->get();

        foreach ($partners as $partner) {
            DB::beginTransaction();
            try {
                $invoice = $this->createPartnerInvoice($partner, $startDate, $endDate);
                $results[] = [
                    'client_id' => $partner->id,
                    'invoice_id' => $invoice->id,
                    'numero_fattura' => $invoice->numero_fattura,
                    'totale' => $invoice->totale,
                    'status' => 'success',
                ];
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $results[] = [
                    'client_id' => $partner->id,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Crea fattura mensile per partner OPPLA
     */
    protected function createPartnerInvoice(Client $partner, Carbon $startDate, Carbon $endDate): Invoice
    {
        // Crea fattura
        $invoice = new Invoice([
            'client_id' => $partner->id,
            'type' => 'attiva',
            'invoice_type' => 'differita',
            'data_emissione' => now(),
            'data_scadenza' => now()->addDays(30),
            'anno' => now()->year,
            'status' => 'emessa',
            'causale' => "Servizi periodo {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}",
        ]);

        // 1. Fee mensile abbonamento
        if ($partner->abbonamento_mensile > 0) {
            $invoice->addItem([
                'descrizione' => 'Abbonamento mensile piattaforma OPPLA',
                'quantita' => 1,
                'prezzo_unitario' => $partner->abbonamento_mensile,
                'iva_percentuale' => 22,
                'service_type' => 'subscription',
            ]);
        }

        // 2. Ordini consegne
        $deliveries = Delivery::where('client_id', $partner->id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('is_invoiced', false)
            ->get();

        if ($deliveries->isNotEmpty()) {
            $totalDeliveryFee = $deliveries->sum('delivery_fee_total');
            $deliveryCount = $deliveries->count();

            $invoice->addItem([
                'descrizione' => "Servizio consegne ($deliveryCount consegne)",
                'quantita' => $deliveryCount,
                'prezzo_unitario' => $totalDeliveryFee / $deliveryCount,
                'iva_percentuale' => 22,
                'service_type' => 'delivery',
            ]);

            // Marca consegne come fatturate
            $deliveries->each(fn($d) => $d->update(['is_invoiced' => true, 'invoice_id' => $invoice->id]));
        }

        // 3. Ordini POS
        $posOrders = PosOrder::where('client_id', $partner->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('is_invoiced', false)
            ->get();

        if ($posOrders->isNotEmpty()) {
            $totalPosFee = $posOrders->sum('fee_oppla');
            $posCount = $posOrders->count();

            $invoice->addItem([
                'descrizione' => "Fee POS ordini elettronici ($posCount transazioni)",
                'quantita' => $posCount,
                'prezzo_unitario' => $totalPosFee / $posCount,
                'iva_percentuale' => 22,
                'service_type' => 'pos',
            ]);

            // Marca ordini come fatturati
            $posOrders->each(fn($p) => $p->update(['is_invoiced' => true, 'invoice_id' => $invoice->id]));
        }

        // 4. Fee ordini
        if ($partner->fee_ordine > 0) {
            $ordiniCount = $deliveries->count() + $posOrders->count();
            if ($ordiniCount > 0) {
                $invoice->addItem([
                    'descrizione' => "Fee gestione ordini ($ordiniCount ordini)",
                    'quantita' => $ordiniCount,
                    'prezzo_unitario' => $partner->fee_ordine,
                    'iva_percentuale' => 22,
                    'service_type' => 'order_fee',
                ]);
            }
        }

        // 5. POS noleggio (se attivo)
        if ($partner->has_pos) {
            $invoice->addItem([
                'descrizione' => 'Noleggio terminale POS',
                'quantita' => 1,
                'prezzo_unitario' => 29.90, // Configurabile
                'iva_percentuale' => 22,
                'service_type' => 'pos_rental',
            ]);
        }

        // Calcola totali
        $invoice->calculateTotals();
        
        // Genera numero fattura e salva
        $invoice->generateInvoiceNumber();
        $invoice->save();

        return $invoice;
    }

    /**
     * Crea fattura ordinaria (cliente extra o servizi extra)
     */
    public function createImmediateInvoice(Client $client, array $items, array $metadata = []): Invoice
    {
        DB::beginTransaction();
        try {
            $invoice = new Invoice([
                'client_id' => $client->id,
                'type' => 'attiva',
                'invoice_type' => 'ordinaria',
                'data_emissione' => now(),
                'data_scadenza' => now()->addDays(30),
                'anno' => now()->year,
                'status' => 'emessa',
                'causale' => $metadata['causale'] ?? 'Vendita servizi',
                'stripe_transaction_id' => $metadata['stripe_transaction_id'] ?? null,
            ]);
            
            // Genera numero fattura DENTRO transazione con lock FOR UPDATE
            $invoice->generateInvoiceNumber();
            $invoice->save();

            foreach ($items as $item) {
                $invoice->addItem($item);
            }

            $invoice->calculateTotals();

            DB::commit();
            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Invia fattura al Sistema Di Interscambio (SDI) tramite Fatture in Cloud
     * 
     * @param Invoice $invoice Fattura da inviare al SDI
     * @param bool $autoCreate Se true, crea automaticamente il documento in FIC se non esiste
     * @return bool True se invio riuscito, false altrimenti
     * @throws \Exception Se errore critico durante l'invio
     */
    public function sendToSDI(Invoice $invoice, bool $autoCreate = true): bool
    {
        // Validazione pre-invio
        $this->validateInvoiceForSDI($invoice);

        // Verifica se già inviata
        if ($invoice->sdi_sent_at) {
            Log::warning("Tentativo di reinvio fattura già inviata al SDI", [
                'invoice_id' => $invoice->id,
                'sdi_sent_at' => $invoice->sdi_sent_at,
            ]);
            throw new \Exception('Fattura già inviata al SDI');
        }

        try {
            // Get active Fatture in Cloud connection
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)
                ->first();

            if (!$ficConnection) {
                throw new \Exception('Nessuna connessione Fatture in Cloud attiva. Configurare OAuth in Impostazioni.');
            }

            $ficService = app(FattureInCloudService::class);

            // Se documento non esiste in FIC, crealo prima
            if (!$invoice->fic_document_id && $autoCreate) {
                Log::info('[InvoicingService] Creazione documento in FIC prima dell\'invio SDI', [
                    'invoice_id' => $invoice->id,
                    'numero_fattura' => $invoice->numero_fattura,
                ]);

                // Prepara oggetto interno basato sul tipo fattura
                $internalSubject = $invoice->oggetto ?? $invoice->note;
                if (!$internalSubject) {
                    $internalSubject = $invoice->invoice_type === 'differita'
                        ? 'Fattura differita ' . $invoice->numero_fattura
                        : 'Fattura ' . $invoice->numero_fattura;
                }

                // Prepara dati fattura per FIC
                $ficInvoiceData = [
                    'type' => 'invoice',
                    'numeration' => '/A', // Numerazione fatture attive
                    'subject' => $internalSubject,
                    'visible_subject' => $invoice->oggetto ?? $invoice->note ?? '',
                    'notes' => $invoice->note ?? '',
                    'use_gross_prices' => false,
                    'e_invoice' => true, // CRITICO: Abilita fatturazione elettronica
                    'ei_type' => 'FPR12', // Fattura verso privati/aziende
                    'ei_description' => 'Fattura elettronica',
                    'payment_method' => [
                        'name' => 'Bonifico bancario',
                        'type' => 'standard',
                    ],
                ];

                // Crea documento in FIC
                $ficResult = $ficService->createInvoiceFromLocal($ficConnection, $invoice, $ficInvoiceData);

                if (!$ficResult || !isset($ficResult['id'])) {
                    throw new \Exception('Impossibile creare fattura in Fatture in Cloud');
                }

                // Salva fic_document_id
                $invoice->fic_document_id = $ficResult['id'];
                $invoice->save();

                Log::info('[InvoicingService] Fattura sincronizzata con FIC', [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $ficResult['id'],
                ]);
            } elseif (!$invoice->fic_document_id) {
                throw new \Exception('Fattura non sincronizzata con Fatture in Cloud. Abilita autoCreate o sincronizza manualmente.');
            }

            // Invia al SDI tramite Fatture in Cloud API
            Log::info('[InvoicingService] Invio fattura al SDI', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
            ]);

            $result = $ficService->sendInvoiceToSDI(
                $ficConnection,
                $invoice->fic_document_id
            );

            if (!$result) {
                throw new \Exception('Impossibile inviare la fattura al SDI. Verifica che il cliente abbia Codice Destinatario o PEC validi.');
            }

            // Download and save XML (optional ma consigliato per conservazione)
            $xml = $ficService->getInvoiceXML(
                $ficConnection,
                $invoice->fic_document_id
            );

            if ($xml) {
                $xmlPath = "invoices/{$invoice->id}/fattura_sdi.xml";
                Storage::disk('local')->put($xmlPath, $xml);
                $invoice->sdi_file_path = $xmlPath;
                Log::debug('XML fattura SDI salvato', ['path' => $xmlPath]);
            }

            // Download and save PDF
            $pdf = $ficService->downloadInvoicePDF(
                $ficConnection,
                $invoice->fic_document_id
            );

            if ($pdf) {
                // Sanitizza numero fattura per nome file (rimuove /)
                $safeInvoiceNumber = str_replace('/', '_', $invoice->numero_fattura);
                $pdfPath = "invoices/{$invoice->id}/fattura_{$safeInvoiceNumber}.pdf";
                Storage::disk('local')->put($pdfPath, $pdf);
                $invoice->pdf_file_path = $pdfPath;
                Log::debug('PDF fattura salvato', ['path' => $pdfPath]);
            }

            // Update invoice status
            $invoice->update([
                'sdi_sent_at' => now(),
                'sdi_status' => 'sent',
                'payment_status' => $invoice->payment_status === 'bozza' ? 'emessa' : $invoice->payment_status,
            ]);

            Log::info('[InvoicingService] Fattura inviata al SDI con successo', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
                'sdi_sent_at' => $invoice->sdi_sent_at,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[InvoicingService] Errore invio SDI', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw exception per permettere al chiamante di gestire l'errore
            throw $e;
        }
    }

    /**
     * Valida che la fattura sia pronta per l'invio al SDI
     * 
     * @param Invoice $invoice
     * @throws \Exception Se validazione fallisce
     */
    private function validateInvoiceForSDI(Invoice $invoice): void
    {
        $errors = [];

        // 1. Verifica cliente
        if (!$invoice->client) {
            $errors[] = 'Cliente mancante';
        } else {
            // 2. Verifica dati SDI cliente
            if (empty($invoice->client->codice_sdi) && empty($invoice->client->pec)) {
                $errors[] = "Cliente '{$invoice->client->ragione_sociale}' senza Codice Destinatario (SDI) o PEC";
            }

            // 3. Verifica P.IVA o Codice Fiscale
            if (empty($invoice->client->piva) && empty($invoice->client->codice_fiscale)) {
                $errors[] = "Cliente '{$invoice->client->ragione_sociale}' senza P.IVA o Codice Fiscale";
            }
        }

        // 4. Verifica numero fattura
        if (empty($invoice->numero_fattura)) {
            $errors[] = 'Numero fattura mancante';
        }

        // 5. Verifica data emissione
        if (!$invoice->data_emissione) {
            $errors[] = 'Data emissione mancante';
        }

        // 6. Verifica items
        if ($invoice->items->isEmpty()) {
            $errors[] = 'Fattura senza righe/items';
        }

        // 7. Verifica totali
        if (!$invoice->totale || $invoice->totale <= 0) {
            $errors[] = 'Totale fattura non valido';
        }

        // 8. Verifica stato fattura
        if ($invoice->payment_status === 'bozza') {
            $errors[] = 'Impossibile inviare fattura in stato BOZZA. Emettere la fattura prima.';
        }

        // Se ci sono errori, lancia eccezione
        if (!empty($errors)) {
            $errorMessage = 'Validazione fattura fallita per invio SDI: ' . implode('; ', $errors);
            
            Log::warning('[InvoicingService] Validazione SDI fallita', [
                'invoice_id' => $invoice->id,
                'numero_fattura' => $invoice->numero_fattura ?? 'N/A',
                'errors' => $errors,
            ]);
            
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Genera PDF fattura (scarica da Fatture in Cloud)
     */
    public function generatePDF(Invoice $invoice): string
    {
        try {
            // Get active Fatture in Cloud connection
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)
                ->first();

            if (!$ficConnection || !$invoice->fic_document_id) {
                // Fallback to local PDF generation if no FIC connection
                return $this->generateLocalPDF($invoice);
            }

            $ficService = app(FattureInCloudService::class);

            // Download PDF from Fatture in Cloud
            $pdf = $ficService->downloadInvoicePDF(
                $ficConnection,
                $invoice->fic_document_id
            );

            if (!$pdf) {
                throw new \Exception('Impossibile scaricare PDF da Fatture in Cloud');
            }

            // Sanitizza il numero fattura per il nome file (rimuove /)
            $safeInvoiceNumber = str_replace('/', '_', $invoice->numero_fattura);
            $pdfPath = "invoices/{$invoice->id}/fattura_{$safeInvoiceNumber}.pdf";
            Storage::disk('local')->put($pdfPath, $pdf);
            
            $invoice->update(['pdf_file_path' => $pdfPath]);
            
            return $pdfPath;
        } catch (\Exception $e) {
            Log::error("Errore generazione PDF fattura {$invoice->id}: {$e->getMessage()}");
            return $this->generateLocalPDF($invoice);
        }
    }

    /**
     * Genera PDF localmente con DOMPDF (fallback - Copia Cortesia)
     */
    private function generateLocalPDF(Invoice $invoice): string
    {
        // Carica cliente e dati azienda
        $client = $invoice->client;
        $companyName = config('app.company_name', 'OPPLA S.R.L.');
        $companyAddress = config('app.company_address', 'Via Example, 1 - 00100 Roma');
        $companyVat = config('app.company_vat', 'IT12345678901');
        
        // HTML del PDF - Copia Cortesia
        $html = view('invoices.pdf', [
            'invoice' => $invoice,
            'client' => $client,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyVat' => $companyVat,
            'isCopyCortesia' => true,
        ])->render();
        
        // Genera PDF con DOMPDF
        $pdf = PDF::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Sanitizza il numero fattura per il nome file (rimuove /)
        $safeInvoiceNumber = str_replace('/', '_', $invoice->numero_fattura);
        $pdfPath = "invoices/{$invoice->id}/fattura_{$safeInvoiceNumber}_cortesia.pdf";
        
        // Salva il PDF
        Storage::disk('local')->put($pdfPath, $pdf->output());
        
        $invoice->update(['pdf_file_path' => $pdfPath]);
        
        return $pdfPath;
    }

    /**
     * Calcola previsioni fatturato mensile
     */
    public function calculateMonthlyRevenueForecast(?Carbon $date = null): array
    {
        $date = $date ?? now();
        
        $partners = Client::partnerOppla()->active()->get();
        
        $forecast = [
            'abbonamenti' => 0,
            'consegne' => 0,
            'pos' => 0,
            'extra' => 0,
            'totale' => 0,
        ];

        foreach ($partners as $partner) {
            $forecast['abbonamenti'] += $partner->abbonamento_mensile;
            $forecast['consegne'] += $partner->fee_consegna_base * 30; // Media giornaliera
            $forecast['pos'] += $partner->has_pos ? 29.90 : 0;
        }

        $forecast['totale'] = array_sum($forecast);

        return $forecast;
    }
}
