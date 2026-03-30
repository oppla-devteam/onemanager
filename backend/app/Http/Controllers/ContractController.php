<?php

namespace App\Http\Controllers;

use App\Http\Traits\CsvExportTrait;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractSignature;
use App\Models\Client;
use App\Services\ContractService;
use App\Services\ContractPdfService;
use App\Services\ContractSignatureService;
use App\Mail\ContractSendMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    use CsvExportTrait;

    public function __construct(
        private ContractService $contractService,
        private ContractPdfService $pdfService,
        private ContractSignatureService $signatureService
    ) {}

    /**
     * Esporta contratti in formato CSV
     */
    public function export(Request $request)
    {
        $query = Contract::with('client');

        if ($request->has('status')) $query->where('status', $request->status);

        $contracts = $query->orderBy('created_at', 'desc')->get();

        $data = [];
        foreach ($contracts as $c) {
            $data[] = [
                'ID' => $c->id,
                'Numero Contratto' => $c->contract_number ?? '',
                'Cliente' => $c->client_name ?? $c->client?->ragione_sociale ?? '',
                'Oggetto' => $c->subject ?? '',
                'Tipo' => $c->contract_type ?? '',
                'Stato' => $c->status ?? '',
                'Data Inizio' => $c->start_date ? \Carbon\Carbon::parse($c->start_date)->format('d/m/Y') : '',
                'Data Fine' => $c->end_date ? \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') : '',
                'Durata (mesi)' => $c->duration_months ?? '',
                'Valore (€)' => number_format($c->value ?? 0, 2, ',', '.'),
                'Rinnovo Auto' => $c->auto_renew ? 'Sì' : 'No',
                'Firmato il' => $c->signed_at ? \Carbon\Carbon::parse($c->signed_at)->format('d/m/Y') : '',
                'Note' => $c->notes ?? $c->note ?? '',
                'Data Creazione' => $c->created_at ? $c->created_at->format('d/m/Y') : '',
            ];
        }

        return $this->streamCsv($data, 'contratti_' . date('Y-m-d_His') . '.csv');
    }

    /**
     * Lista contratti
     */
    public function index(Request $request)
    {
        $query = Contract::with(['client', 'signatures', 'creator'])
            ->orderBy('created_at', 'desc');

        // Filtri
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->has('expiring')) {
            $query->expiring($request->expiring ?? 30);
        }

        $contracts = $request->has('per_page') 
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($contracts);
    }

    /**
     * Dettaglio contratto
     */
    public function show(Contract $contract)
    {
        $contract->load([
            'client',
            'template',
            'signatures',
            'history.user',
            'attachments',
            'creator',
            'assignee'
        ]);

        return response()->json($contract);
    }

    /**
     * Crea nuovo contratto
     */
    public function store(Request $request)
    {
        // Se arriva da template system (con template_id)
        if ($request->has('template_id')) {
            $validated = $request->validate([
                'template_id' => 'required|exists:contract_templates,id',
                'client_id' => 'nullable|exists:clients,id',
                'contract_data' => 'required|array',
                'signers' => 'required|array|min:1',
                'signers.*.name' => 'required|string',
                'signers.*.email' => 'required|email',
                'signers.*.role' => 'required|string',
                'signers.*.order' => 'nullable|integer',
            ]);

            try {
                $template = ContractTemplate::findOrFail($validated['template_id']);
                
                $contract = $this->contractService->createFromTemplate(
                    $template,
                    $validated['contract_data'],
                    $validated['signers'],
                    $validated['client_id'] ?? null
                );

                return response()->json($contract->load('signatures'), 201);
            } catch (\Exception $e) {
                Log::error('Errore creazione contratto', [
                    'error' => $e->getMessage(),
                    'data' => $validated,
                ]);

                return response()->json([
                    'error' => 'Errore creazione contratto',
                    'message' => $e->getMessage()
                ], 422);
            }
        }
        
        // Se arriva da form semplice (senza template)
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'contract_type' => 'required|string|in:servizio,fornitura,partnership,lavoro',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'value' => 'nullable|numeric|min:0',
            // Informazioni Partner
            'partner_ragione_sociale' => 'nullable|string',
            'partner_piva' => 'nullable|string',
            'partner_sede_legale' => 'nullable|string',
            'partner_iban' => 'nullable|string',
            'partner_legale_rappresentante' => 'nullable|string',
            'partner_email' => 'nullable|email',
            // Durata e zona
            'periodo_mesi' => 'nullable|integer',
            'territorio' => 'nullable|string',
            // Sito
            'site_name' => 'nullable|string',
            'site_address' => 'nullable|string',
            // Costi
            'costo_attivazione' => 'nullable|numeric',
            // Servizi
            'servizio_ritiro' => 'nullable|numeric',
            'servizio_principale' => 'nullable|numeric',
            'ordine_rifiutato' => 'nullable|numeric',
            'abbonamento_consegne' => 'nullable|numeric',
            'inserimento_manuale' => 'nullable|numeric',
            // Opzioni
            'attrezzatura_fornita' => 'nullable|boolean',
            'miglior_prezzo_garantito' => 'nullable|boolean',
        ]);

        try {
            // Prepara termini del contratto (tutti i campi extra)
            $terms = [
                'partner' => [
                    'ragione_sociale' => $validated['partner_ragione_sociale'] ?? null,
                    'piva' => $validated['partner_piva'] ?? null,
                    'sede_legale' => $validated['partner_sede_legale'] ?? null,
                    'iban' => $validated['partner_iban'] ?? null,
                    'legale_rappresentante' => $validated['partner_legale_rappresentante'] ?? null,
                    'email' => $validated['partner_email'] ?? null,
                ],
                'durata' => [
                    'periodo_mesi' => $validated['periodo_mesi'] ?? 12,
                    'territorio' => $validated['territorio'] ?? 'Italia',
                ],
                'sito' => [
                    'nome' => $validated['site_name'] ?? null,
                    'indirizzo' => $validated['site_address'] ?? null,
                ],
                'costi' => [
                    'attivazione' => $validated['costo_attivazione'] ?? null,
                ],
                'servizi' => [
                    'ritiro' => $validated['servizio_ritiro'] ?? null,
                    'principale' => $validated['servizio_principale'] ?? null,
                    'ordine_rifiutato' => $validated['ordine_rifiutato'] ?? null,
                    'abbonamento_consegne' => $validated['abbonamento_consegne'] ?? null,
                    'inserimento_manuale' => $validated['inserimento_manuale'] ?? null,
                ],
                'opzioni' => [
                    'attrezzatura_fornita' => $validated['attrezzatura_fornita'] ?? false,
                    'miglior_prezzo_garantito' => $validated['miglior_prezzo_garantito'] ?? false,
                ],
            ];

            // Crea contratto
            $contract = Contract::create([
                'client_id' => $validated['client_id'],
                'title' => $validated['title'],
                'contract_type' => $validated['contract_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'value' => $validated['value'] ?? 0,
                'duration_months' => $validated['periodo_mesi'] ?? null,
                'status' => 'bozza',
                'terms' => $terms,
                'created_by' => auth()->id(),
            ]);

            $contract->logHistory('created', null, 'bozza', $validated);

            return response()->json([
                'success' => true,
                'data' => $contract->load('client'),
                'message' => 'Contratto creato con successo'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Errore creazione contratto', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore creazione contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Aggiorna contratto
     */
    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'contract_data' => 'sometimes|array',
            'subject' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'notes' => 'sometimes|string',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        try {
            if (isset($validated['contract_data'])) {
                $contract = $this->contractService->updateDraft($contract, $validated);
            } else {
                $contract->update($validated);
            }

            return response()->json($contract->fresh()->load('signatures'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore aggiornamento contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Elimina contratto (soft delete)
     */
    public function destroy(Contract $contract)
    {
        if (in_array($contract->status, ['signed', 'active'])) {
            return response()->json([
                'error' => 'Non è possibile eliminare un contratto firmato o attivo'
            ], 422);
        }

        $contract->delete();

        return response()->json(['message' => 'Contratto eliminato con successo']);
    }

    /**
     * Prepara contratto per invio
     */
    public function prepare(Contract $contract)
    {
        try {
            $contract = $this->contractService->prepareForSending($contract);
            return response()->json($contract);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore preparazione contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Invia contratto per firma
     */
    public function send(Contract $contract, Request $request)
    {
        $validated = $request->validate([
            'custom_message' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        try {
            // Se viene specificato un client_id, aggiorna il contratto
            if (isset($validated['client_id'])) {
                $client = Client::findOrFail($validated['client_id']);
                $contract->update([
                    'client_id' => $client->id,
                    'client_name' => $client->ragione_sociale,
                    'client_email' => $client->email,
                    'client_phone' => $client->phone,
                    'client_vat_number' => $client->piva,
                    'client_fiscal_code' => $client->codice_fiscale,
                ]);
            }

            // Genera PDF se non esiste
            if (!$contract->pdf_path) {
                $this->pdfService->generatePdf($contract);
            }

            // Invia contratto tramite servizio
            $contract = $this->contractService->sendForSignature($contract);

            // Invia email al cliente
            Mail::to($contract->client_email)
                ->send(new ContractSendMail(
                    $contract,
                    $validated['custom_message'] ?? null
                ));

            return response()->json([
                'message' => 'Contratto inviato con successo',
                'contract' => $contract->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Errore invio contratto', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Errore invio contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Ottieni lista clienti per selezione
     */
    public function getClients(Request $request)
    {
        $query = Client::select('id', 'ragione_sociale', 'email', 'phone', 'piva', 'type')
            ->where('status', 'active');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ragione_sociale', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('piva', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('ragione_sociale')->get();

        return response()->json($clients);
    }

    /**
     * Attiva contratto
     */
    public function activate(Contract $contract)
    {
        try {
            $contract = $this->contractService->activateContract($contract);
            return response()->json($contract);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore attivazione contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Termina contratto
     */
    public function terminate(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $contract = $this->contractService->terminateContract($contract, $validated['reason']);
            return response()->json($contract);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore terminazione contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Annulla contratto
     */
    public function cancel(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $contract = $this->contractService->cancelContract($contract, $validated['reason']);
            return response()->json($contract);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore annullamento contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Rinnova contratto
     */
    public function renew(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'new_data' => 'sometimes|array',
        ]);

        try {
            $newContract = $this->contractService->renewContract(
                $contract,
                $validated['new_data'] ?? []
            );
            return response()->json($newContract, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore rinnovo contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Duplica contratto
     */
    public function duplicate(Contract $contract)
    {
        try {
            $duplicate = $this->contractService->duplicateContract($contract);
            return response()->json($duplicate, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore duplicazione contratto',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Download PDF
     */
    public function downloadPdf(Contract $contract, Request $request)
    {
        $signed = $request->boolean('signed', false);
        $path = $signed ? $contract->signed_pdf_path : $contract->pdf_path;

        if (!$path) {
            return response()->json([
                'error' => 'PDF non disponibile'
            ], 404);
        }

        try {
            return $this->pdfService->downloadPdf($path);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore download PDF',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Visualizza PDF
     */
    public function viewPdf(Contract $contract, Request $request)
    {
        $signed = $request->boolean('signed', false);
        $path = $signed ? $contract->signed_pdf_path : $contract->pdf_path;

        if (!$path) {
            return response()->json([
                'error' => 'PDF non disponibile'
            ], 404);
        }

        try {
            return $this->pdfService->streamPdf($path);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore visualizzazione PDF',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Statistiche contratti
     */
    public function statistics()
    {
        $stats = [
            'total' => Contract::count(),
            'by_status' => Contract::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'active' => Contract::active()->count(),
            'expiring_soon' => Contract::expiring(30)->count(),
            'recent' => Contract::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Invia contratto per firma digitale
     */
    public function sendForSignatureEmail(Contract $contract, Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'signer_name' => 'required|string',
            'signer_role' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Genera PDF se non esiste
            if (!$contract->pdf_path) {
                $pdfPath = $this->pdfService->generatePdf($contract);
                $contract->update(['pdf_path' => $pdfPath]);
            }

            // Crea signature record
            $signature = ContractSignature::create([
                'contract_id' => $contract->id,
                'signer_name' => $validated['signer_name'],
                'signer_email' => $validated['email'],
                'signer_role' => $validated['signer_role'] ?? 'Legale Rappresentante',
                'signing_order' => 1,
                'status' => 'pending',
                'signature_token' => bin2hex(random_bytes(32)),
                'token_expires_at' => now()->addDays(30),
            ]);

            // Aggiorna stato contratto
            $contract->update([
                'status' => 'pending_signature'
            ]);

            // Invia email con link per firma
            $this->signatureService->sendSignatureInvitation($signature, $validated['message'] ?? null);

            DB::commit();

            // Log attività
            activity()
                ->performedOn($contract)
                ->withProperties([
                    'email' => $validated['email'],
                    'signer_name' => $validated['signer_name']
                ])
                ->log('Contratto inviato per firma digitale');

            return response()->json([
                'message' => 'Contratto inviato con successo',
                'sent_to' => $validated['email'],
                'signature_url' => route('contracts.sign', ['token' => $signature->signature_token]),
                'contract' => $contract->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore invio contratto per firma', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Errore invio contratto',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
