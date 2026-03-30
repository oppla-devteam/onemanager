<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomaticInvoicingService
{
    protected $ficService;

    public function __construct(FattureInCloudService $ficService)
    {
        $this->ficService = $ficService;
    }

    /**
     * Crea fattura immediata per ordine con pagamento online (Stripe)
     */
    public function createImmediateInvoiceForDelivery(Delivery $delivery): Invoice
    {
        DB::beginTransaction();

        try {
            // Verifica che sia pagamento online
            if ($delivery->payment_method !== 'online') {
                throw new \Exception('Delivery non ha pagamento online');
            }

            // Verifica che non esista già fattura
            if ($delivery->invoice_id) {
                throw new \Exception('Fattura già esistente per questa consegna');
            }

            $client = $delivery->client;

            // Crea fattura locale
            $invoice = $this->createLocalInvoice($delivery, 'immediate');

            // Sincronizza cliente con FIC
            $ficClient = $this->syncClientToFIC($client);

            // Get active FIC connection
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)
                ->first();

            if (!$ficConnection) {
                throw new \Exception('Nessuna connessione Fatture in Cloud attiva');
            }

            // Prepara dati fattura per FIC
            $ficInvoiceData = [
                'client_fic_id' => $ficClient['id'] ?? null,
                'client_name' => $client->business_name,
                'client_vat' => $client->vat_number,
                'client_tax_code' => $client->tax_code,
                'client_address' => $client->address,
                'client_city' => $client->city,
                'client_province' => $client->province,
                'client_zip' => $client->zip_code,
                'client_pec' => $client->pec_email,
                'client_sdi_code' => $client->sdi_code,
                'date' => $invoice->data_emissione->format('Y-m-d'),
                'number' => $invoice->numero_progressivo,
                'numeration' => '/FE',
                'items' => $this->buildInvoiceItems($delivery),
                'total' => $invoice->totale,
                'payment_method' => 'Stripe / Carta di credito',
            ];

            // Crea fattura su FIC usando il nuovo metodo
            $ficResponse = $this->ficService->createImmediateInvoice(
                $ficConnection,
                $ficInvoiceData
            );

            // Salva ID FIC
            $invoice->fic_document_id = $ficResponse['id'] ?? null;
            $invoice->save();

            // Aggiorna delivery
            $delivery->invoice_id = $invoice->id;
            $delivery->save();

            // Invia a SDI se abilitato
            if (config('services.fattureincloud.auto_send_sdi', true)) {
                $this->sendToSDI($invoice);
            }

            DB::commit();

            Log::info('Fattura immediata creata', [
                'invoice_id' => $invoice->id,
                'delivery_id' => $delivery->id,
                'fic_id' => $ficResponse['id'] ?? null,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione fattura immediata: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Genera fatture differite mensili per pagamenti contanti
     */
    public function generateMonthlyDeferredInvoices(int $month, int $year): array
    {
        DB::beginTransaction();

        try {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Trova tutte le consegne con pagamento contanti senza fattura
            $deliveries = Delivery::where('payment_method', 'cash')
                ->whereBetween('delivery_date', [$startDate, $endDate])
                ->whereNull('invoice_id')
                ->with('client')
                ->get();

            if ($deliveries->isEmpty()) {
                Log::info('Nessuna consegna da fatturare', ['month' => $month, 'year' => $year]);
                return [];
            }

            // Raggruppa per cliente
            $deliveriesByClient = $deliveries->groupBy('client_id');

            $invoices = [];

            foreach ($deliveriesByClient as $clientId => $clientDeliveries) {
                $client = $clientDeliveries->first()->client;

                // Crea DDT per ogni consegna
                $ddtReferences = [];
                foreach ($clientDeliveries as $delivery) {
                    $ddt = $this->createDDT($delivery);
                    $ddtReferences[] = [
                        'id' => $ddt->fic_document_id,
                        'date' => $ddt->data_emissione->format('Y-m-d'),
                        'number' => $ddt->numero_fattura,
                    ];
                }

                // Crea fattura differita riepilogativa
                $invoice = $this->createDeferredInvoiceForClient($client, $clientDeliveries, $ddtReferences, $month, $year);

                $invoices[] = $invoice;
            }

            DB::commit();

            Log::info('Fatture differite generate', [
                'count' => count($invoices),
                'month' => $month,
                'year' => $year,
            ]);

            return $invoices;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore generazione fatture differite: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crea DDT per una consegna
     */
    private function createDDT(Delivery $delivery): Invoice
    {
        $client = $delivery->client;

        // Crea DDT locale
        $ddt = Invoice::create([
            'client_id' => $client->id,
            'type' => 'attiva',
            'invoice_type' => 'ddt',
            'data_emissione' => $delivery->delivery_date,
            'imponibile' => $delivery->subtotal,
            'iva' => $delivery->vat_amount,
            'totale' => $delivery->total,
            'status' => 'emessa',
            'payment_status' => 'non_pagata',
            'payment_method' => 'cash',
        ]);

        $ddt->generateInvoiceNumber();

        // Crea items
        InvoiceItem::create([
            'invoice_id' => $ddt->id,
            'description' => "Consegna #{$delivery->id} - {$delivery->delivery_date->format('d/m/Y')}",
            'quantity' => 1,
            'unit_price' => $delivery->subtotal,
            'vat_rate' => 22,
            'total' => $delivery->total,
        ]);

        // Sincronizza con FIC
        $ficClient = $this->syncClientToFIC($client);

        $ficDdtData = [
            'client_fic_id' => $ficClient['id'] ?? null,
            'client_name' => $client->business_name,
            'client_vat' => $client->vat_number,
            'client_tax_code' => $client->tax_code,
            'client_address' => $client->address,
            'client_city' => $client->city,
            'client_province' => $client->province,
            'client_zip' => $client->zip_code,
            'date' => $ddt->data_emissione->format('Y-m-d'),
            'number' => $ddt->numero_progressivo,
            'items' => [[
                'name' => "Servizio consegna",
                'description' => "Consegna del {$delivery->delivery_date->format('d/m/Y')}",
                'quantity' => 1,
                'unit_price' => $delivery->subtotal,
                'vat_rate' => 22,
            ]],
            'total' => $ddt->totale,
        ];

        $ficResponse = $this->ficService->createDDT($ficDdtData);

        // Salva ID FIC
        $ddt->fic_document_id = $ficResponse['data']['id'] ?? null;
        $ddt->save();

        // Aggiorna delivery
        $delivery->ddt_id = $ddt->id;
        $delivery->save();

        return $ddt;
    }

    /**
     * Crea fattura differita per un cliente
     */
    private function createDeferredInvoiceForClient(Client $client, $deliveries, array $ddtReferences, int $month, int $year): Invoice
    {
        $totalImponibile = $deliveries->sum('subtotal');
        $totalIva = $deliveries->sum('vat_amount');
        $totalAmount = $deliveries->sum('total');

        // Crea fattura locale
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'type' => 'attiva',
            'invoice_type' => 'differita',
            'data_emissione' => Carbon::create($year, $month)->endOfMonth(),
            'data_scadenza' => Carbon::create($year, $month)->endOfMonth()->addDays(30),
            'imponibile' => $totalImponibile,
            'iva' => $totalIva,
            'totale' => $totalAmount,
            'status' => 'emessa',
            'payment_status' => 'non_pagata',
            'payment_method' => 'bank_transfer',
            'causale' => "Fattura differita riepilogativa mese " . Carbon::create($year, $month)->format('m/Y'),
        ]);

        $invoice->generateInvoiceNumber();

        // Crea items riepilogativo
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Servizi di consegna mese " . Carbon::create($year, $month)->format('m/Y') . " - {$deliveries->count()} consegne",
            'quantity' => $deliveries->count(),
            'unit_price' => $totalImponibile / $deliveries->count(),
            'vat_rate' => 22,
            'total' => $totalAmount,
        ]);

        // Associa le delivery alla fattura
        foreach ($deliveries as $delivery) {
            $delivery->invoice_id = $invoice->id;
            $delivery->save();
        }

        // Sincronizza con FIC
        $ficClient = $this->syncClientToFIC($client);

        $ficInvoiceData = [
            'client_fic_id' => $ficClient['id'] ?? null,
            'client_name' => $client->business_name,
            'client_vat' => $client->vat_number,
            'client_tax_code' => $client->tax_code,
            'client_address' => $client->address,
            'client_city' => $client->city,
            'client_province' => $client->province,
            'client_zip' => $client->zip_code,
            'client_pec' => $client->pec_email,
            'client_sdi_code' => $client->sdi_code,
            'date' => $invoice->data_emissione->format('Y-m-d'),
            'number' => $invoice->numero_progressivo,
            'items' => [[
                'name' => "Servizi di consegna",
                'description' => "Servizi di consegna mese " . Carbon::create($year, $month)->format('m/Y') . " - {$deliveries->count()} consegne",
                'quantity' => $deliveries->count(),
                'unit_price' => $totalImponibile / $deliveries->count(),
                'vat_rate' => 22,
            ]],
            'total' => $totalAmount,
            'payment_method' => 'Bonifico bancario',
            'ddt_references' => $ddtReferences,
        ];

        $ficResponse = $this->ficService->createDeferredInvoice($ficInvoiceData);

        // Salva ID FIC
        $invoice->fic_document_id = $ficResponse['data']['id'] ?? null;
        $invoice->save();

        // Invia a SDI
        if (config('services.fattureincloud.auto_send_sdi', true)) {
            $this->sendToSDI($invoice);
        }

        return $invoice;
    }

    /**
     * Crea fattura locale
     */
    private function createLocalInvoice(Delivery $delivery, string $type): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $delivery->client_id,
            'type' => 'attiva',
            'invoice_type' => $type,
            'data_emissione' => now(),
            'imponibile' => $delivery->subtotal,
            'iva' => $delivery->vat_amount,
            'totale' => $delivery->total,
            'status' => 'emessa',
            'payment_status' => $type === 'immediate' ? 'pagata' : 'non_pagata',
            'payment_method' => $delivery->payment_method === 'online' ? 'stripe' : 'cash',
            'stripe_transaction_id' => $delivery->transaction_id,
        ]);

        $invoice->generateInvoiceNumber();

        // Crea invoice items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Servizio consegna - Ordine #{$delivery->id}",
            'quantity' => 1,
            'unit_price' => $delivery->subtotal,
            'vat_rate' => 22,
            'total' => $delivery->total,
        ]);

        return $invoice;
    }

    /**
     * Sincronizza cliente con Fatture in Cloud
     */
    private function syncClientToFIC(Client $client): array
    {
        $clientData = [
            'name' => $client->business_name,
            'vat_number' => $client->vat_number,
            'tax_code' => $client->tax_code,
            'address' => $client->address,
            'city' => $client->city,
            'province' => $client->province,
            'zip' => $client->zip_code,
            'email' => $client->email,
            'pec' => $client->pec_email,
            'phone' => $client->phone,
            'sdi_code' => $client->sdi_code,
        ];

        $response = $this->ficService->syncClient($clientData);

        // Salva FIC ID sul cliente
        if (isset($response['data']['id'])) {
            $client->fic_id = $response['data']['id'];
            $client->save();
        }

        return $response['data'] ?? [];
    }

    /**
     * Invia fattura a SDI tramite Fatture in Cloud
     */
    private function sendToSDI(Invoice $invoice): void
    {
        if (!$invoice->fic_document_id) {
            throw new \Exception('Fattura non sincronizzata con Fatture in Cloud');
        }

        try {
            // Get active Fatture in Cloud connection
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)
                ->first();

            if (!$ficConnection) {
                throw new \Exception('Nessuna connessione Fatture in Cloud attiva');
            }

            // Send to SDI
            $result = $this->ficService->sendInvoiceToSDI(
                $ficConnection,
                $invoice->fic_document_id
            );

            if (!$result) {
                throw new \Exception('Errore durante l\'invio al SDI');
            }

            // Download XML and PDF
            $xml = $this->ficService->getInvoiceXML(
                $ficConnection,
                $invoice->fic_document_id
            );

            if ($xml) {
                $xmlPath = "invoices/{$invoice->id}/fattura_sdi.xml";
                \Storage::disk('local')->put($xmlPath, $xml);
                $invoice->sdi_file_path = $xmlPath;
            }

            $pdf = $this->ficService->downloadInvoicePDF(
                $ficConnection,
                $invoice->fic_document_id
            );

            if ($pdf) {
                $pdfPath = "invoices/{$invoice->id}/fattura_{$invoice->numero_fattura}.pdf";
                \Storage::disk('local')->put($pdfPath, $pdf);
                $invoice->pdf_file_path = $pdfPath;
            }

            $invoice->sdi_status = 'sent';
            $invoice->sdi_sent_at = now();
            $invoice->save();

            Log::info('Fattura inviata a SDI', ['invoice_id' => $invoice->id]);

        } catch (\Exception $e) {
            Log::error('Errore invio SDI: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Costruisce items per FIC dalla delivery
     */
    private function buildInvoiceItems(Delivery $delivery): array
    {
        return [[
            'name' => 'Servizio consegna',
            'description' => "Consegna #{$delivery->id} del {$delivery->delivery_date->format('d/m/Y')}",
            'quantity' => 1,
            'unit_price' => $delivery->subtotal,
            'vat_rate' => 22,
        ]];
    }
}
