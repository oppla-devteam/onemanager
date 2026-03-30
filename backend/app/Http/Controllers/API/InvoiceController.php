<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\CsvExportTrait;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Services\InvoicingService;
use App\Services\AutomaticInvoicingService;
use App\Services\PaymentInvoicingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use CsvExportTrait;

    protected $invoicingService;

    public function __construct(InvoicingService $invoicingService)
    {
        $this->invoicingService = $invoicingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'items']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_fattura', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('ragione_sociale', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('data_emissione', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('data_emissione', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'data_emissione');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination - aumentato a 1000 come default per mostrare tutte le fatture
        // Il frontend gestisce la paginazione lato client
        $perPage = $request->get('per_page', 1000);
        $invoices = $query->paginate($perPage);

        return response()->json($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type' => 'required|in:ordinaria,differita',
            'data_emissione' => 'required|date',
            'data_scadenza' => 'required|date|after:data_emissione',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0|max:100',
        ]);

        // Create invoice
        $invoiceData = collect($validated)->except('items')->toArray();
        $invoice = new Invoice($invoiceData);
        
        // Generate invoice number inside transaction
        $invoice->generateInvoiceNumber();
        $invoice->save();

        // Create invoice items (map English field names to Italian DB columns)
        foreach ($validated['items'] as $item) {
            $invoice->items()->create([
                'descrizione' => $item['description'],
                'quantita' => $item['quantity'],
                'prezzo_unitario' => $item['unit_price'],
                'iva_percentuale' => $item['vat_rate'],
            ]);
        }

        // Calculate totals
        $invoice->calculateTotals();

        return response()->json($invoice->load('items'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = Invoice::with(['client', 'items'])->findOrFail($id);
        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Can't update if already sent to SDI or paid
        if ($invoice->sdi_sent_at || $invoice->payment_status === 'pagata') {
            return response()->json([
                'message' => 'Impossibile modificare una fattura già inviata o pagata'
            ], 422);
        }

        $validated = $request->validate([
            'type' => 'sometimes|in:ordinaria,differita',
            'data_emissione' => 'sometimes|date',
            'data_scadenza' => 'sometimes|date|after:data_emissione',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.vat_rate' => 'required_with:items|numeric|min:0|max:100',
        ]);

        // Update invoice
        $invoiceData = collect($validated)->except('items')->toArray();
        $invoice->update($invoiceData);

        // Update items if provided
        if (isset($validated['items'])) {
            $invoice->items()->delete();
            foreach ($validated['items'] as $item) {
                $invoice->items()->create($item);
            }
        }

        // Recalculate totals
        $invoice->calculateTotals();

        return response()->json($invoice->load('items'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Can't delete if already sent to FIC, SDI or paid
        if ($invoice->fic_document_id || $invoice->sdi_sent_at || $invoice->payment_status === 'pagata') {
            return response()->json([
                'message' => 'Impossibile eliminare una fattura già inviata a Fatture in Cloud, SDI o pagata'
            ], 422);
        }

        // Hard delete (forceDelete bypasses soft deletes)
        $invoice->forceDelete();

        return response()->json([
            'message' => 'Fattura eliminata definitivamente'
        ]);
    }

    /**
     * Download PDF from Fatture in Cloud
     */
    public function downloadPdfFromFIC(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if (!$invoice->fic_document_id) {
            return response()->json([
                'success' => false,
                'message' => 'Fattura non ancora inviata a Fatture in Cloud. Invia prima la fattura.'
            ], 422);
        }

        try {
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$ficConnection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna connessione attiva a Fatture in Cloud'
                ], 422);
            }

            $ficService = app(\App\Services\FattureInCloudService::class);
            $pdfContent = $ficService->downloadInvoicePDF($ficConnection, $invoice->fic_document_id);

            if (!$pdfContent) {
                throw new \Exception('Impossibile scaricare il PDF da Fatture in Cloud');
            }

            $filename = 'Fattura-' . str_replace('/', '_', $invoice->numero_fattura) . '.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore download PDF da FIC', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il download del PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm/verify invoice in Fatture in Cloud (change status from draft to confirmed)
     */
    /**
     * Verifica fattura in FIC e invia a SDI
     * POST /api/invoices/{id}/confirm
     */
    public function confirmInvoice(string $id)
    {
        $invoice = Invoice::findOrFail($id);

        if (!$invoice->fic_document_id) {
            return response()->json([
                'success' => false,
                'message' => 'Fattura non ancora creata in Fatture in Cloud. Invia prima la fattura con il pulsante "Invia".'
            ], 422);
        }

        if ($invoice->sdi_sent_at) {
            return response()->json([
                'success' => false,
                'message' => 'Fattura già inviata al SDI'
            ], 422);
        }

        try {
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$ficConnection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna connessione attiva a Fatture in Cloud'
                ], 422);
            }

            $ficService = app(\App\Services\FattureInCloudService::class);
            
            Log::info('[InvoiceController] Verifica e invio fattura a SDI', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id
            ]);
            
            // 1. Cambia stato in 'not_sent' (confermata, pronta per SDI)
            $statusResult = $ficService->changeInvoiceStatus($ficConnection, $invoice->fic_document_id, 'not_sent');

            if (!$statusResult) {
                throw new \Exception('Impossibile confermare la fattura in Fatture in Cloud');
            }

            // 2. Invia al SDI
            $sdiResult = $ficService->sendInvoiceToSDI($ficConnection, $invoice->fic_document_id);
            
            if (!$sdiResult) {
                Log::warning('[InvoiceController] Fattura confermata in FIC ma invio SDI fallito', [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Fattura confermata in Fatture in Cloud ma impossibile inviarla al SDI. Verifica che il cliente abbia Codice Destinatario (7 cifre) o PEC validi.',
                    'invoice' => $invoice,
                    'warning' => 'Fattura confermata ma non inviata al SDI'
                ], 422);
            }

            // 3. Aggiorna stato locale
            $invoice->sdi_sent_at = now();
            $invoice->sdi_status = 'sent';
            $invoice->status = 'emessa';
            $invoice->save();

            Log::info('[InvoiceController] Fattura verificata e inviata al SDI', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
                'sdi_sent_at' => $invoice->sdi_sent_at
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fattura verificata e inviata al SDI con successo',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore verifica/invio SDI', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la verifica/invio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send invoice to SDI (Sistema di Interscambio) via Fatture in Cloud
     */
    public function sendToSDI(string $id)
    {
        $invoice = Invoice::with('client', 'items')->findOrFail($id);

        if ($invoice->sdi_sent_at) {
            return response()->json([
                'success' => false,
                'message' => 'Fattura già inviata al SDI'
            ], 422);
        }

        try {
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$ficConnection) {
                throw new \Exception('Nessuna connessione Fatture in Cloud attiva. Configurare OAuth in Impostazioni.');
            }

            $ficService = app(\App\Services\FattureInCloudService::class);
            
            // 1. Prima sincronizza con Fatture in Cloud se non è già stata sincronizzata
            if (!$invoice->fic_document_id) {
                Log::info('[InvoiceController] Creazione documento in FIC', [
                    'invoice_id' => $invoice->id,
                    'numero_fattura' => $invoice->numero_fattura
                ]);
                
                // Prepara oggetto interno basato sul tipo fattura
                $internalSubject = $invoice->oggetto ?? $invoice->description;
                if (!$internalSubject) {
                    $internalSubject = $invoice->invoice_type === 'differita'
                        ? 'Fattura differita ' . $invoice->numero_fattura
                        : 'Fattura ' . $invoice->numero_fattura;
                }

                // Prepara dati fattura per FIC
                $ficInvoiceData = [
                    'type' => 'invoice',
                    'numeration' => '/A', // Sia ordinarie che differite usano /A
                    'subject' => $internalSubject,
                    'visible_subject' => $invoice->oggetto ?? $invoice->description ?? '',
                    'rc_center' => '',
                    'notes' => $invoice->note ?? '',
                    'rivalsa' => 0,
                    'cassa' => 0,
                    'withholding_tax' => 0,
                    'withholding_tax_taxable' => 0,
                    'other_withholding_tax' => 0,
                    'use_gross_prices' => false,
                    'e_invoice' => true, // Abilita fatturazione elettronica
                    'ei_type' => $invoice->invoice_type === 'differita' ? 'TD24' : 'TD01', // TD24 per differite, TD01 per ordinarie
                    'ei_description' => 'Fattura elettronica',
                    'ei_payment_method' => 'MP05', // MP05 = Bonifico bancario
                    'ei_vat_kind' => 'I', // I = IVA ad esigibilità immediata (default)
                    'editable' => true,
                    'is_marked' => false,
                    'payment_method' => [
                        'name' => 'Bonifico bancario',
                        'type' => 'standard'
                    ],
                ];
                
                // Crea la fattura in FIC e salva il document_id
                $ficResult = $ficService->createInvoiceFromLocal($ficConnection, $invoice, $ficInvoiceData);
                
                if (!$ficResult || !isset($ficResult['id'])) {
                    throw new \Exception('Impossibile creare fattura in Fatture in Cloud');
                }
                
                // Salva fic_document_id
                $invoice->fic_document_id = $ficResult['id'];
                $invoice->save();
                
                Log::info('[InvoiceController] Fattura sincronizzata con FIC', [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $ficResult['id']
                ]);
            }
            
            // 2. Ora invia al SDI tramite API di Fatture in Cloud
            Log::info('[InvoiceController] Invio fattura al SDI', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id
            ]);
            
            $sdiResult = $ficService->sendInvoiceToSDI($ficConnection, $invoice->fic_document_id);
            
            if (!$sdiResult) {
                // La fattura è comunque in FIC, ma non è stata inviata al SDI
                Log::warning('[InvoiceController] Fattura creata in FIC ma invio SDI fallito', [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Fattura creata in Fatture in Cloud ma impossibile inviarla al SDI. Verifica che il cliente abbia Codice Destinatario (7 cifre) o PEC validi e riprova dalla pagina Fatture.',
                    'invoice' => $invoice,
                    'fic_document_id' => $invoice->fic_document_id,
                    'warning' => 'La fattura è stata salvata ma non inviata al SDI'
                ], 422);
            }
            
            // 3. Aggiorna stato fattura locale
            $invoice->sdi_sent_at = now();
            $invoice->sdi_status = 'sent';
            if ($invoice->payment_status === 'bozza') {
                $invoice->payment_status = 'emessa';
            }
            $invoice->save();
            
            Log::info('[InvoiceController] Fattura inviata al SDI con successo', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id,
                'sdi_sent_at' => $invoice->sdi_sent_at
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Fattura inviata a Fatture in Cloud e al SDI con successo',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id,
                    'sdi_sent_at' => $invoice->sdi_sent_at,
                    'sdi_status' => $invoice->sdi_status,
                    'fic_response' => $sdiResult
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore invio SDI: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'invio: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invia fattura SOLO a Fatture in Cloud (senza invio SDI)
     * POST /api/invoices/{id}/send-to-fic
     */
    public function sendToFIC(string $id)
    {
        $invoice = Invoice::with('client', 'items')->findOrFail($id);

        // Controllo se già inviata a FIC
        if ($invoice->fic_document_id) {
            Log::warning('[InvoiceController] Tentativo di reinvio fattura già in FIC', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $invoice->fic_document_id
            ]);

            return response()->json([
                'success' => true, // Cambiato a true per non bloccare il flusso bulk
                'message' => 'Fattura già presente in Fatture in Cloud (saltata)',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id,
                    'already_sent' => true
                ]
            ]);
        }

        try {
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();

            if (!$ficConnection) {
                throw new \Exception('Nessuna connessione Fatture in Cloud attiva. Configurare OAuth in Impostazioni.');
            }

            $ficService = app(\App\Services\FattureInCloudService::class);

            Log::info('[InvoiceController] Creazione documento in FIC (senza SDI)', [
                'invoice_id' => $invoice->id,
                'numero_fattura' => $invoice->numero_fattura
            ]);

            // Prepara oggetto interno basato sul tipo fattura
            $internalSubject = $invoice->oggetto ?? $invoice->description;
            if (!$internalSubject) {
                $internalSubject = $invoice->invoice_type === 'differita'
                    ? 'Fattura differita ' . $invoice->numero_fattura
                    : 'Fattura ' . $invoice->numero_fattura;
            }

            // Prepara dati fattura per FIC
            $ficInvoiceData = [
                'type' => 'invoice',
                'numeration' => '/A', // Sia ordinarie che differite usano /A
                'subject' => $internalSubject,
                'visible_subject' => $invoice->oggetto ?? $invoice->description ?? '',
                'rc_center' => '',
                'notes' => $invoice->note ?? '',
                'rivalsa' => 0,
                'cassa' => 0,
                'withholding_tax' => 0,
                'withholding_tax_taxable' => 0,
                'other_withholding_tax' => 0,
                'use_gross_prices' => false,
                'e_invoice' => true,
                'ei_type' => $invoice->invoice_type === 'differita' ? 'TD24' : 'TD01', // TD24 per differite, TD01 per ordinarie
                'ei_description' => 'Fattura elettronica',
                'ei_payment_method' => 'MP05',
                'ei_vat_kind' => 'I',
                'editable' => true,
                'is_marked' => false,
                'payment_method' => [
                    'name' => 'Bonifico bancario',
                    'type' => 'standard'
                ],
            ];

            // Crea la fattura in FIC
            $ficResult = $ficService->createInvoiceFromLocal($ficConnection, $invoice, $ficInvoiceData);

            if (!$ficResult || !isset($ficResult['id'])) {
                throw new \Exception('Impossibile creare fattura in Fatture in Cloud');
            }

            // Salva fic_document_id
            $invoice->fic_document_id = $ficResult['id'];
            $invoice->save();

            Log::info('[InvoiceController] Fattura creata in FIC (pronta per verifica SDI)', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $ficResult['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fattura creata in Fatture in Cloud con successo. Usa "Verifica" per inviarla al SDI.',
                'data' => [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore invio FIC: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'numero_fattura' => $invoice->numero_fattura ?? 'N/A',
                'data_emissione' => $invoice->data_emissione ?? 'N/A',
                'trace' => $e->getTraceAsString()
            ]);

            // Se l'errore contiene parole chiave che indicano conflitto di numerazione
            $errorMessage = $e->getMessage();
            $isNumberConflict = stripos($errorMessage, 'duplicate') !== false
                             || stripos($errorMessage, 'conflitto') !== false
                             || stripos($errorMessage, 'già esistente') !== false
                             || stripos($errorMessage, 'numero fattura') !== false
                             || stripos($errorMessage, 'data successiva ma numero inferiore') !== false
                             || stripos($errorMessage, 'data precedente ma numero superiore') !== false;

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione in FIC: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'can_retry_next_day' => $isNumberConflict, // Indica se si può ritentare con data successiva
                'invoice_id' => $invoice->id,
                'current_date' => $invoice->data_emissione ? $invoice->data_emissione->format('Y-m-d') : null,
            ], 422);
        }
    }

    /**
     * Ritenta invio fattura a FIC con data del giorno successivo
     * POST /api/invoices/{id}/retry-next-day
     */
    public function retryWithNextDay(string $id)
    {
        $invoice = Invoice::with('client', 'items')->findOrFail($id);

        // Verifica che non sia già in FIC
        if ($invoice->fic_document_id) {
            return response()->json([
                'success' => false,
                'message' => 'Fattura già presente in Fatture in Cloud'
            ], 422);
        }

        // Verifica che abbia una data di emissione
        if (!$invoice->data_emissione) {
            return response()->json([
                'success' => false,
                'message' => 'Fattura senza data di emissione'
            ], 422);
        }

        try {
            Log::info('[InvoiceController] Retry con data successiva', [
                'invoice_id' => $invoice->id,
                'old_date' => $invoice->data_emissione->format('Y-m-d'),
                'old_numero' => $invoice->numero_fattura
            ]);

            // Sposta la data al giorno successivo
            $oldDate = $invoice->data_emissione->copy();
            $invoice->data_emissione = $invoice->data_emissione->addDay();

            // Se c'è una data di scadenza, spostala anche quella
            if ($invoice->data_scadenza) {
                $invoice->data_scadenza = $invoice->data_scadenza->addDay();
            }

            // Resetta il numero fattura per rigenerarlo
            $invoice->numero_fattura = null;
            $invoice->numero_progressivo = null;

            // Rigenera il numero fattura con la nuova data
            \DB::transaction(function () use ($invoice) {
                $invoice->generateInvoiceNumber();
                $invoice->save();
            });

            Log::info('[InvoiceController] Numero fattura rigenerato', [
                'invoice_id' => $invoice->id,
                'new_date' => $invoice->data_emissione->format('Y-m-d'),
                'new_numero' => $invoice->numero_fattura
            ]);

            // Ora prova a inviare a FIC
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();

            if (!$ficConnection) {
                throw new \Exception('Nessuna connessione Fatture in Cloud attiva');
            }

            $ficService = app(\App\Services\FattureInCloudService::class);

            // Prepara oggetto interno basato sul tipo fattura
            $internalSubject = $invoice->oggetto ?? $invoice->description;
            if (!$internalSubject) {
                $internalSubject = $invoice->invoice_type === 'differita'
                    ? 'Fattura differita ' . $invoice->numero_fattura
                    : 'Fattura ' . $invoice->numero_fattura;
            }

            $ficInvoiceData = [
                'type' => 'invoice',
                'numeration' => '/A',
                'subject' => $internalSubject,
                'visible_subject' => $invoice->oggetto ?? $invoice->description ?? '',
                'rc_center' => '',
                'notes' => $invoice->note ?? '',
                'rivalsa' => 0,
                'cassa' => 0,
                'withholding_tax' => 0,
                'withholding_tax_taxable' => 0,
                'other_withholding_tax' => 0,
                'use_gross_prices' => false,
                'e_invoice' => true,
                'ei_type' => $invoice->invoice_type === 'differita' ? 'TD24' : 'TD01', // TD24 per differite, TD01 per ordinarie
                'ei_description' => 'Fattura elettronica',
                'ei_payment_method' => 'MP05',
                'ei_vat_kind' => 'I',
                'editable' => true,
                'is_marked' => false,
                'payment_method' => [
                    'name' => 'Bonifico bancario',
                    'type' => 'standard'
                ],
            ];

            $ficResult = $ficService->createInvoiceFromLocal($ficConnection, $invoice, $ficInvoiceData);

            if (!$ficResult || !isset($ficResult['id'])) {
                // Se fallisce di nuovo, ripristina la data originale
                $invoice->data_emissione = $oldDate;
                if ($invoice->data_scadenza) {
                    $invoice->data_scadenza = $invoice->data_scadenza->subDay();
                }
                $invoice->save();

                throw new \Exception('Impossibile creare fattura in Fatture in Cloud anche con nuova data');
            }

            // Salva fic_document_id
            $invoice->fic_document_id = $ficResult['id'];
            $invoice->save();

            Log::info('[InvoiceController] Fattura creata in FIC con nuova data', [
                'invoice_id' => $invoice->id,
                'fic_document_id' => $ficResult['id'],
                'new_date' => $invoice->data_emissione->format('Y-m-d'),
                'new_numero' => $invoice->numero_fattura
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fattura creata con successo con nuova data: ' . $invoice->data_emissione->format('d/m/Y'),
                'data' => [
                    'invoice_id' => $invoice->id,
                    'fic_document_id' => $invoice->fic_document_id,
                    'new_date' => $invoice->data_emissione->format('Y-m-d'),
                    'new_numero' => $invoice->numero_fattura,
                    'old_date' => $oldDate->format('Y-m-d')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore retry con data successiva', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il retry: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifica date emissione in bulk per più fatture
     * POST /api/invoices/bulk-update-dates
     */
    public function bulkUpdateDates(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
            'new_date' => 'required|date',
            'update_due_date' => 'boolean', // Se true, aggiorna anche data_scadenza mantenendo lo stesso offset
        ]);

        try {
            $invoiceIds = $validated['invoice_ids'];
            $newDate = Carbon::parse($validated['new_date']);
            $updateDueDate = $validated['update_due_date'] ?? false;

            $updated = 0;
            $errors = [];

            DB::transaction(function () use ($invoiceIds, $newDate, $updateDueDate, &$updated, &$errors) {
                foreach ($invoiceIds as $invoiceId) {
                    try {
                        $invoice = Invoice::findOrFail($invoiceId);

                        // Non permettere modifica se già inviata a FIC o SDI
                        if ($invoice->fic_document_id || $invoice->sdi_sent_at) {
                            $errors[] = "Fattura {$invoice->numero_fattura}: già inviata a FIC/SDI, impossibile modificare";
                            continue;
                        }

                        $oldDate = $invoice->data_emissione;
                        $oldDueDate = $invoice->data_scadenza;

                        // Aggiorna data emissione
                        $invoice->data_emissione = $newDate;

                        // Se richiesto, aggiorna anche la data di scadenza mantenendo lo stesso offset
                        if ($updateDueDate && $oldDate && $oldDueDate) {
                            $daysDiff = $oldDate->diffInDays($oldDueDate);
                            $invoice->data_scadenza = $newDate->copy()->addDays($daysDiff);
                        }

                        // Resetta il numero fattura per rigenerarlo con la nuova data
                        $invoice->numero_fattura = null;
                        $invoice->numero_progressivo = null;

                        // Rigenera numero fattura
                        $invoice->generateInvoiceNumber();
                        $invoice->save();

                        $updated++;

                        Log::info('[BulkUpdateDates] Fattura aggiornata', [
                            'invoice_id' => $invoice->id,
                            'old_date' => $oldDate->format('Y-m-d'),
                            'new_date' => $newDate->format('Y-m-d'),
                            'new_numero' => $invoice->numero_fattura
                        ]);

                    } catch (\Exception $e) {
                        $errors[] = "Fattura ID {$invoiceId}: {$e->getMessage()}";
                        Log::error('[BulkUpdateDates] Errore aggiornamento fattura', [
                            'invoice_id' => $invoiceId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Aggiornate {$updated} fatture su " . count($invoiceIds),
                'data' => [
                    'updated_count' => $updated,
                    'total_requested' => count($invoiceIds),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('[BulkUpdateDates] Errore generale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(string $id, Request $request)
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        $invoice->markAsPaid(
            $validated['payment_date'],
            $validated['payment_method'] ?? null,
            $validated['transaction_id'] ?? null
        );

        return response()->json([
            'message' => 'Fattura marcata come pagata',
            'invoice' => $invoice
        ]);
    }

    /**
     * Esporta fatture in formato CSV
     */
    public function export(Request $request)
    {
        $query = Invoice::with('client');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        if ($request->has('date_from')) {
            $query->whereDate('data_emissione', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('data_emissione', '<=', $request->date_to);
        }

        $invoices = $query->orderBy('data_emissione', 'desc')->get();

        $data = [];
        foreach ($invoices as $inv) {
            $data[] = [
                'ID' => $inv->id,
                'Numero Fattura' => $inv->numero_fattura ?? '',
                'Tipo' => $inv->type ?? '',
                'Tipo Fattura' => $inv->invoice_type ?? '',
                'Data Emissione' => $inv->data_emissione ? $inv->data_emissione->format('d/m/Y') : '',
                'Data Scadenza' => $inv->data_scadenza ? $inv->data_scadenza->format('d/m/Y') : '',
                'Cliente' => $inv->client?->ragione_sociale ?? 'N/A',
                'P.IVA Cliente' => $inv->client?->piva ?? '',
                'Imponibile (€)' => number_format($inv->imponibile ?? 0, 2, ',', '.'),
                'IVA (€)' => number_format($inv->iva ?? 0, 2, ',', '.'),
                'Totale (€)' => number_format($inv->totale ?? 0, 2, ',', '.'),
                'Stato Pagamento' => $inv->payment_status ?? '',
                'Metodo Pagamento' => $inv->payment_method ?? '',
                'SDI' => $inv->sdi_status ?? '',
                'Causale' => $inv->causale ?? '',
                'Note' => $inv->note ?? '',
            ];
        }

        return $this->streamCsv($data, 'fatture_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Get invoice statistics
     */
    public function stats(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $stats = [
            'total' => Invoice::count(),
            'current_month' => Invoice::whereMonth('data_emissione', $month)
                ->whereYear('data_emissione', $year)
                ->count(),
            'by_status' => [
                'bozza' => Invoice::where('payment_status', 'bozza')->count(),
                'emessa' => Invoice::where('payment_status', 'emessa')->count(),
                'pagata' => Invoice::where('payment_status', 'pagata')->count(),
                'scaduta' => Invoice::scadute()->count(),
            ],
            'amounts' => [
                'total' => Invoice::sum('total_amount'),
                'unpaid' => Invoice::whereIn('payment_status', ['bozza', 'emessa'])->sum('total_amount'),
                'paid' => Invoice::where('payment_status', 'pagata')->sum('total_amount'),
                'overdue' => Invoice::scadute()->sum('total_amount'),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePDF(string $id)
    {
        $invoice = Invoice::with(['client', 'items'])->findOrFail($id);

        // TODO: Implement PDF generation
        // This would typically use a library like dompdf or snappy

        return response()->json([
            'message' => 'PDF generation not yet implemented',
            'invoice' => $invoice
        ]);
    }

    /**
     * Preview fattura da pagamenti aggregati
     */
    public function previewFromPayments(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:bank_transactions,id',
        ]);

        try {
            $client = Client::findOrFail($validated['client_id']);
            $paymentInvoicingService = new PaymentInvoicingService();
            
            $preview = $paymentInvoicingService->previewInvoiceFromPayments(
                $client,
                $validated['payment_ids']
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);

        } catch (\Exception $e) {
            Log::error('[PreviewInvoice] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la preview della fattura',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera fattura da pagamenti
     */
    public function generateFromPayments(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:bank_transactions,id',
            'invoice_data' => 'required|array',
            'invoice_data.data_emissione' => 'required|date',
            'invoice_data.data_scadenza' => 'nullable|date',
            'invoice_data.payment_method' => 'nullable|string',
            'invoice_data.note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.descrizione' => 'required|string',
            'items.*.quantita' => 'required|numeric|min:1',
            'items.*.prezzo_unitario' => 'required|numeric|min:0',
            'items.*.iva_percentuale' => 'required|numeric|min:0',
            'items.*.imponibile' => 'required|numeric|min:0',
            'items.*.iva' => 'required|numeric|min:0',
        ]);

        try {
            $client = Client::findOrFail($validated['client_id']);
            $paymentInvoicingService = new PaymentInvoicingService();
            
            $invoice = $paymentInvoicingService->generateInvoiceFromPayments(
                $client,
                $validated['payment_ids'],
                $validated['invoice_data'],
                $validated['items']
            );

            return response()->json([
                'success' => true,
                'message' => 'Fattura generata con successo',
                'data' => $invoice,
            ], 201);

        } catch (\Exception $e) {
            Log::error('[GenerateInvoice] Errore: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione della fattura',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create credit note from existing invoice
     */
    public function createCreditNote(string $id, Request $request)
    {
        try {
            $originalInvoice = Invoice::findOrFail($id);

            // Validate that original invoice exists and is valid
            if ($originalInvoice->invoice_type === 'nota_credito') {
                return response()->json([
                    'message' => 'Non è possibile creare una nota di credito da un\'altra nota di credito'
                ], 422);
            }

            $validated = $request->validate([
                'reason' => 'required|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.vat_rate' => 'required|numeric|min:0|max:100',
                'partial' => 'boolean',
            ]);

            // Create credit note
            $creditNote = new Invoice([
                'client_id' => $originalInvoice->client_id,
                'type' => 'attiva',
                'invoice_type' => 'nota_credito',
                'anno' => now()->year,
                'data_emissione' => now(),
                'riferimento_fattura_id' => $originalInvoice->id,
                'note' => 'Nota di credito per fattura ' . $originalInvoice->numero_fattura . '. Motivo: ' . $validated['reason'],
            ]);
            
            // Generate invoice number WITHIN transaction with lock FOR UPDATE
            $creditNote->generateInvoiceNumber();
            $creditNote->save();

            // Create credit note items (negative amounts)
            foreach ($validated['items'] as $item) {
                $creditNote->items()->create([
                    'description' => $item['description'],
                    'quantity' => -abs($item['quantity']), // Negative for credit note
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'],
                ]);
            }

            // Calculate totals
            $creditNote->calculateTotals();

            // Get FIC connection and create credit note on Fatture in Cloud
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();
            
            if ($ficConnection) {
                $ficService = app(\App\Services\FattureInCloudService::class);
                $client = $originalInvoice->client;

                $ficInvoiceData = [
                    'client_fic_id' => $client->fic_client_id,
                    'client_name' => $client->ragione_sociale,
                    'client_vat' => $client->piva,
                    'client_tax_code' => $client->codice_fiscale,
                    'date' => $creditNote->data_emissione->format('Y-m-d'),
                    'number' => $creditNote->numero_progressivo,
                    'numeration' => '/NC',
                    'items' => array_map(function ($item) {
                        return [
                            'name' => $item['description'],
                            'qty' => abs($item['quantity']),
                            'net_price' => $item['unit_price'],
                            'vat' => ['percentage' => $item['vat_rate']],
                        ];
                    }, $validated['items']),
                ];

                $ficResponse = $ficService->createCreditNote(
                    $ficConnection,
                    $ficInvoiceData,
                    $originalInvoice->fic_document_id
                );

                if ($ficResponse) {
                    $creditNote->fic_document_id = $ficResponse['id'] ?? null;
                    $creditNote->save();

                    // Send to SDI if auto-send is enabled
                    if (config('services.fattureincloud.auto_send_sdi', true)) {
                        $invoicingService = app(\App\Services\InvoicingService::class);
                        $invoicingService->sendToSDI($creditNote);
                    }
                }
            }

            return response()->json([
                'message' => 'Nota di credito creata con successo',
                'data' => $creditNote->load('items', 'client')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating credit note: ' . $e->getMessage());
            return response()->json([
                'message' => 'Errore durante la creazione della nota di credito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download invoice PDF
     */
    public function downloadPDF(string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            // Check if PDF already exists
            if ($invoice->pdf_file_path && \Storage::disk('local')->exists($invoice->pdf_file_path)) {
                return \Storage::disk('local')->download($invoice->pdf_file_path);
            }

            // Generate PDF
            $invoicingService = app(\App\Services\InvoicingService::class);
            $pdfPath = $invoicingService->generatePDF($invoice);

            if (\Storage::disk('local')->exists($pdfPath)) {
                return \Storage::disk('local')->download($pdfPath);
            }

            return response()->json([
                'message' => 'PDF non disponibile'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error downloading PDF: ' . $e->getMessage());
            return response()->json([
                'message' => 'Errore durante il download del PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check SDI status for an invoice
     */
    public function checkSDIStatus(string $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if (!$invoice->fic_document_id) {
                return response()->json([
                    'message' => 'Fattura non sincronizzata con Fatture in Cloud'
                ], 404);
            }

            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$ficConnection) {
                return response()->json([
                    'message' => 'Nessuna connessione Fatture in Cloud attiva'
                ], 500);
            }

            $ficService = app(\App\Services\FattureInCloudService::class);
            $status = $ficService->getInvoiceStatus($ficConnection, $invoice->fic_document_id);

            if (!$status) {
                return response()->json([
                    'message' => 'Impossibile recuperare lo stato della fattura'
                ], 500);
            }

            // Update local invoice with latest status
            if (isset($status['e_invoice_sent']) && $status['e_invoice_sent']) {
                $invoice->update([
                    'sdi_status' => 'sent',
                    'sdi_sent_at' => $invoice->sdi_sent_at ?? now(),
                ]);
            }

            return response()->json([
                'message' => 'Stato fattura recuperato',
                'data' => [
                    'local_status' => $invoice->sdi_status,
                    'fic_status' => $status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking SDI status: ' . $e->getMessage());
            return response()->json([
                'message' => 'Errore durante il controllo dello stato SDI',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizza lo stato delle fatture con Fatture in Cloud
     * Recupera fatture attive e passive e aggiorna lo stato locale
     */
    public function syncWithFIC(Request $request)
    {
        try {
            $ficConnection = \App\Models\FattureInCloudConnection::where('is_active', true)->first();
            
            if (!$ficConnection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna connessione Fatture in Cloud attiva'
                ], 422);
            }

            $ficService = app(\App\Services\FattureInCloudService::class);
            $created = 0;
            $updated = 0;
            $activeCount = 0;
            $passiveCount = 0;
            $skipped = 0;
            
            // 1. Sincronizza fatture attive (emesse) - IMPORT COMPLETO
            Log::info('[InvoiceController] Sync FIC - Recupero fatture attive');
            $activeInvoices = $ficService->getIssuedInvoices($ficConnection, [
                'type' => 'invoice', // REQUIRED: Specifica che vogliamo fatture (non quotes, orders, etc.)
                'per_page' => 100,
                'page' => 1
            ]);
            
            // Log CRITICO per debug
            Log::info('[InvoiceController] Risposta getIssuedInvoices', [
                'is_null' => $activeInvoices === null,
                'is_array' => is_array($activeInvoices),
                'has_data_key' => isset($activeInvoices['data']),
                'type' => gettype($activeInvoices),
                'keys' => is_array($activeInvoices) ? array_keys($activeInvoices) : 'N/A'
            ]);
            
            if ($activeInvoices && isset($activeInvoices['data'])) {
                $activeCount = count($activeInvoices['data']);
                
                Log::info('[InvoiceController] Fatture attive da FIC', [
                    'total' => $activeCount,
                    'first_invoice_sample' => $activeCount > 0 ? [
                        'id' => $activeInvoices['data'][0]['id'] ?? null,
                        'number' => $activeInvoices['data'][0]['number'] ?? null,
                        'date' => $activeInvoices['data'][0]['date'] ?? null,
                        'amount' => $activeInvoices['data'][0]['amount_gross'] ?? null,
                        'entity_id' => $activeInvoices['data'][0]['entity']['id'] ?? null,
                        'entity_name' => $activeInvoices['data'][0]['entity']['name'] ?? null,
                        'entity_email' => $activeInvoices['data'][0]['entity']['email'] ?? null,
                    ] : null
                ]);
                
                foreach ($activeInvoices['data'] as $ficInvoice) {
                    try {
                        $localInvoice = Invoice::where('fic_document_id', $ficInvoice['id'])->first();
                        
                        if ($localInvoice) {
                            // AGGIORNA fattura esistente
                            $needsUpdate = false;
                            $updateData = [];
                            
                            // Check SDI status
                            if (isset($ficInvoice['e_invoice']) && $ficInvoice['e_invoice']) {
                                if (!$localInvoice->sdi_sent_at && isset($ficInvoice['ei_status']) && $ficInvoice['ei_status'] !== 'draft') {
                                    $updateData['sdi_sent_at'] = now();
                                    $updateData['sdi_status'] = 'sent';
                                    $needsUpdate = true;
                                }
                            }
                            
                            // Check payment status
                            if (isset($ficInvoice['status'])) {
                                $newStatus = match($ficInvoice['status']) {
                                    'draft' => 'bozza',
                                    'not_sent' => 'emessa',
                                    'sent' => 'inviata',
                                    'paid' => 'pagata',
                                    default => $localInvoice->status
                                };
                                
                                if ($newStatus !== $localInvoice->status) {
                                    $updateData['status'] = $newStatus;
                                    $needsUpdate = true;
                                }
                            }
                            
                            if ($needsUpdate) {
                                $localInvoice->update($updateData);
                                $updated++;
                            }
                        } else {
                            // IMPORTA nuova fattura attiva da FIC
                            Log::info('[InvoiceController] Tentativo import nuova fattura', [
                                'fic_id' => $ficInvoice['id'],
                                'number' => $ficInvoice['number'] ?? 'N/A',
                                'entity' => $ficInvoice['entity'] ?? null
                            ]);
                            
                            // Cerca cliente per fic_client_id o email
                            $client = null;
                            if (isset($ficInvoice['entity']['id'])) {
                                $client = Client::where('fic_client_id', $ficInvoice['entity']['id'])->first();
                                Log::info('[InvoiceController] Ricerca cliente per fic_client_id', [
                                    'fic_client_id' => $ficInvoice['entity']['id'],
                                    'found' => $client ? 'SI' : 'NO'
                                ]);
                            }
                            
                            // Se non trovato per fic_client_id, cerca per email
                            if (!$client && isset($ficInvoice['entity']['email'])) {
                                $client = Client::where('email', $ficInvoice['entity']['email'])->first();
                                Log::info('[InvoiceController] Ricerca cliente per email', [
                                    'email' => $ficInvoice['entity']['email'],
                                    'found' => $client ? 'SI' : 'NO'
                                ]);
                            }
                            
                            // Se non trovato, cerca per nome azienda (fallback)
                            if (!$client && isset($ficInvoice['entity']['name'])) {
                                $client = Client::where('ragione_sociale', 'LIKE', '%' . $ficInvoice['entity']['name'] . '%')->first();
                                Log::info('[InvoiceController] Ricerca cliente per nome', [
                                    'name' => $ficInvoice['entity']['name'],
                                    'found' => $client ? 'SI' : 'NO'
                                ]);
                            }
                            
                            if ($client) {
                                Log::info('[InvoiceController] Cliente trovato, procedo con creazione', [
                                    'client_id' => $client->id,
                                    'client_name' => $client->ragione_sociale
                                ]);
                                
                                // Estrai numero progressivo dal numero fattura (es. "15/A" -> 15)
                                $numeroProgressivo = 0;
                                $numeroFattura = $ficInvoice['number'] ?? 'N/A';
                                
                                // Se il numero FIC non ha già suffisso /A, aggiungilo
                                if (isset($ficInvoice['number']) && preg_match('/^(\d+)$/', $ficInvoice['number'], $matches)) {
                                    // Solo numero senza suffisso (es. "2") -> aggiungi /A
                                    $numeroProgressivo = (int)$matches[1];
                                    $numeroFattura = "{$numeroProgressivo}/A";
                                } elseif (isset($ficInvoice['number']) && preg_match('/^(\d+)\/([A-Z]+)$/', $ficInvoice['number'], $matches)) {
                                    // Numero con suffisso già presente (es. "2/A") -> usa così com'è
                                    $numeroProgressivo = (int)$matches[1];
                                    $numeroFattura = $ficInvoice['number'];
                                }
                                
                                // Determina tipo fattura (ordinaria/differita) dal description o causale
                                $invoiceType = 'ordinaria';
                                if (isset($ficInvoice['causale']) && stripos($ficInvoice['causale'], 'differit') !== false) {
                                    $invoiceType = 'differita';
                                }
                                
                                $invoiceData = [
                                    'client_id' => $client->id,
                                    'type' => 'attiva',
                                    'invoice_type' => $invoiceType,
                                    'numero_fattura' => $numeroFattura,
                                    'anno' => isset($ficInvoice['date']) ? date('Y', strtotime($ficInvoice['date'])) : date('Y'),
                                    'numero_progressivo' => $numeroProgressivo,
                                    'data_emissione' => $ficInvoice['date'] ?? now(),
                                    'data_scadenza' => $ficInvoice['due_date'] ?? null,
                                    'imponibile' => $ficInvoice['amount_net'] ?? 0,
                                    'iva' => $ficInvoice['amount_vat'] ?? 0,
                                    'totale' => $ficInvoice['amount_gross'] ?? 0,
                                    'status' => match($ficInvoice['status'] ?? 'draft') {
                                        'draft' => 'bozza',
                                        'not_sent' => 'emessa',
                                        'sent' => 'inviata',
                                        'paid' => 'pagata',
                                        default => 'emessa'
                                    },
                                    'payment_status' => ($ficInvoice['status'] ?? '') === 'paid' ? 'pagata' : 'non_pagata',
                                    'fic_document_id' => $ficInvoice['id'],
                                    'sdi_sent_at' => (isset($ficInvoice['ei_status']) && $ficInvoice['ei_status'] !== 'draft') ? now() : null,
                                    'causale' => $ficInvoice['causale'] ?? null,
                                    'note' => $ficInvoice['notes'] ?? null,
                                ];
                                
                                Log::info('[InvoiceController] Dati fattura da creare', ['invoice_data' => $invoiceData]);
                                
                                $newInvoice = Invoice::create($invoiceData);
                                
                                $created++;
                                
                                Log::info('[InvoiceController] Fattura attiva importata da FIC', [
                                    'fic_id' => $ficInvoice['id'],
                                    'numero' => $ficInvoice['number'] ?? 'N/A',
                                    'client_id' => $client->id,
                                    'invoice_id' => $newInvoice->id
                                ]);
                            } else {
                                $skipped++;
                                Log::warning('[InvoiceController] Cliente non trovato per fattura FIC', [
                                    'fic_invoice_id' => $ficInvoice['id'],
                                    'fic_invoice_number' => $ficInvoice['number'] ?? 'N/A',
                                    'entity_name' => $ficInvoice['entity']['name'] ?? 'N/A',
                                    'entity_email' => $ficInvoice['entity']['email'] ?? 'N/A'
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $skipped++;
                        Log::error('[InvoiceController] Errore importazione fattura attiva', [
                            'fic_invoice_id' => $ficInvoice['id'] ?? 'N/A',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // 2. Sincronizza fatture passive (ricevute)
            Log::info('[InvoiceController] Sync FIC - Recupero fatture passive');
            $passiveInvoices = $ficService->getReceivedDocuments($ficConnection, [
                'per_page' => 100,
                'page' => 1
            ]);
            
            if ($passiveInvoices && isset($passiveInvoices['data'])) {
                $passiveCount = count($passiveInvoices['data']);
                
                foreach ($passiveInvoices['data'] as $ficPassive) {
                    try {
                        // Crea o aggiorna fattura passiva locale
                        $localInvoice = Invoice::where('fic_document_id', $ficPassive['id'])
                            ->where('type', 'passiva')
                            ->first();
                        
                        if (!$localInvoice && isset($ficPassive['entity']['id'])) {
                            // Cerca cliente
                            $client = Client::where('fic_client_id', $ficPassive['entity']['id'])->first();
                            
                            // Fallback: cerca per email o nome
                            if (!$client && isset($ficPassive['entity']['email'])) {
                                $client = Client::where('email', $ficPassive['entity']['email'])->first();
                            }
                            if (!$client && isset($ficPassive['entity']['name'])) {
                                $client = Client::where('ragione_sociale', 'LIKE', '%' . $ficPassive['entity']['name'] . '%')->first();
                            }
                            
                            if ($client) {
                                Invoice::create([
                                    'client_id' => $client->id,
                                    'type' => 'passiva',
                                    'invoice_type' => 'ordinaria',
                                    'numero_fattura' => $ficPassive['number'] ?? 'N/A',
                                    'anno' => isset($ficPassive['date']) ? date('Y', strtotime($ficPassive['date'])) : date('Y'),
                                    'numero_progressivo' => (int)($ficPassive['number'] ?? 0),
                                    'data_emissione' => $ficPassive['date'] ?? now(),
                                    'data_scadenza' => $ficPassive['due_date'] ?? null,
                                    'imponibile' => $ficPassive['amount_net'] ?? 0,
                                    'iva' => $ficPassive['amount_vat'] ?? 0,
                                    'totale' => $ficPassive['amount_gross'] ?? 0,
                                    'status' => 'emessa',
                                    'payment_status' => ($ficPassive['status'] ?? '') === 'paid' ? 'pagata' : 'non_pagata',
                                    'fic_document_id' => $ficPassive['id'],
                                    'note' => $ficPassive['notes'] ?? null,
                                ]);
                                $created++;
                            } else {
                                $skipped++;
                            }
                        }
                    } catch (\Exception $e) {
                        $skipped++;
                        Log::error('[InvoiceController] Errore importazione fattura passiva', [
                            'fic_passive_id' => $ficPassive['id'] ?? 'N/A',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Log::info('[InvoiceController] Sync FIC completata', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'active_invoices' => $activeCount,
                'passive_invoices' => $passiveCount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronizzazione completata con successo',
                'data' => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'active_invoices' => $activeCount,
                    'passive_invoices' => $passiveCount,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore sincronizzazione FIC: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera fatture differite mensili per pagamenti contanti
     */
    public function generateMonthlyDeferred(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $month = $validated['month'];
        $year = $validated['year'];

        try {
            $automaticService = app(AutomaticInvoicingService::class);
            $invoices = $automaticService->generateMonthlyDeferredInvoices($month, $year);

            if (empty($invoices)) {
                return response()->json([
                    'success' => true,
                    'message' => "Nessuna fattura differita da generare per {$month}/{$year}",
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Generate " . count($invoices) . " fatture differite per {$month}/{$year}",
                'data' => $invoices,
            ]);
        } catch (\Exception $e) {
            Log::error('[InvoiceController] Errore generazione fatture differite: ' . $e->getMessage(), [
                'month' => $month,
                'year' => $year,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la generazione: ' . $e->getMessage(),
            ], 500);
        }
    }
}
