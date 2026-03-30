<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Client;
use App\Services\ContractService;
use App\Services\ContractPdfService;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function __construct(
        private ContractService $contractService,
        private ContractPdfService $pdfService
    ) {}

    public function index(Request $request)
    {
        $query = Contract::with('client');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('ragione_sociale', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'contract_type' => 'required|in:servizio,fornitura,partnership,lavoro',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'duration_months' => 'nullable|integer|min:1',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'billing_frequency' => 'nullable|in:monthly,quarterly,yearly,one_time',
            'auto_renew' => 'nullable|boolean',
            'note' => 'nullable|string',
        ]);

        $validated['status'] = 'bozza';
        $contract = Contract::create($validated);
        return response()->json($contract->load('client'), 201);
    }

    /**
     * Create contract from onboarding flow
     */
    public function createFromOnboarding(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'value' => 'nullable|numeric|min:0',
            // Dati Partner
            'partner_ragione_sociale' => 'required|string',
            'partner_piva' => 'required|string',
            'partner_sede_legale' => 'required|string',
            'partner_iban' => 'nullable|string',
            'partner_legale_rappresentante' => 'required|string',
            'partner_email' => 'required|email',
            // Durata e zona
            'periodo_mesi' => 'required|integer|min:1',
            'territorio' => 'required|string',
            // Sito
            'site_name' => 'required|string',
            'site_address' => 'required|string',
            // Costi
            'costo_attivazione' => 'required|numeric|min:0',
            // Servizi
            'servizio_ritiro' => 'required|numeric|min:0',
            'servizio_principale' => 'required|numeric|min:0',
            'ordine_rifiutato' => 'required|numeric|min:0',
            'abbonamento_consegne' => 'required|numeric|min:0',
            'inserimento_manuale' => 'required|numeric|min:0',
            // Opzioni
            'attrezzatura_fornita' => 'boolean',
            'miglior_prezzo_garantito' => 'boolean',
        ]);

        try {
            $client = Client::findOrFail($validated['client_id']);

            // Prepara dati per il contratto
            $restaurantData = [
                'nome' => $validated['site_name'],
                'indirizzo' => $validated['site_address'],
                'best_price' => $validated['miglior_prezzo_garantito'] ?? false,
            ];

            $feeData = [
                'activation_fee' => $validated['costo_attivazione'],
                'pickup_fee' => $validated['servizio_ritiro'],
                'main_service_fee' => $validated['servizio_principale'],
                'rejected_order_fee' => $validated['ordine_rifiutato'],
                'manual_entry_fee' => $validated['inserimento_manuale'],
                'delivery_subscription' => $validated['abbonamento_consegne'],
                'equipment_provided' => $validated['attrezzatura_fornita'] ?? true,
            ];

            // Usa il service per creare contratto da onboarding (stesso flusso)
            $contract = $this->contractService->createFromOnboarding(
                $validated['client_id'],
                $restaurantData,
                $feeData
            );

            return response()->json($contract->load('client'), 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download PDF del contratto
     */
    public function downloadPdf(string $id)
    {
        $contract = Contract::findOrFail($id);
        
        if (!$contract->pdf_path) {
            // Genera PDF se non esiste
            $this->pdfService->generatePdf($contract);
            $contract->refresh();
        }
        
        if (!Storage::exists($contract->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF non trovato'
            ], 404);
        }
        
        return Storage::download(
            $contract->pdf_path,
            "contratto_{$contract->contract_number}.pdf"
        );
    }

    /**
     * Visualizza PDF del contratto nel browser
     */
    public function viewPdf(string $id)
    {
        $contract = Contract::findOrFail($id);
        
        if (!$contract->pdf_path) {
            // Genera PDF se non esiste
            $this->pdfService->generatePdf($contract);
            $contract->refresh();
        }
        
        if (!Storage::exists($contract->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF non trovato'
            ], 404);
        }
        
        return response()->file(
            Storage::path($contract->pdf_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="contratto_' . $contract->contract_number . '.pdf"'
            ]
        );
    }

    /**
     * Invia contratto via email per firma
     */
    public function sendForSignatureEmail(string $id)
    {
        try {
            $contract = Contract::findOrFail($id);
            
            // Usa il service per inviare
            $this->contractService->sendForSignature($contract);
            
            return response()->json([
                'success' => true,
                'message' => 'Inviti per firma inviati con successo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'invio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiche contratti
     */
    public function statistics()
    {
        return response()->json([
            'total' => Contract::count(),
            'active' => Contract::where('status', 'active')->count(),
            'pending_signature' => Contract::where('status', 'pending_signature')->orWhere('status', 'ready_to_sign')->count(),
            'expired' => Contract::where('status', 'expired')->count(),
            'draft' => Contract::where('status', 'draft')->count(),
        ]);
    }

    /**
     * Lista clienti per dropdown
     */
    public function getClients()
    {
        $clients = Client::select('id', 'ragione_sociale as name', 'email', 'piva as vat_number', 'indirizzo as address', 'iban', 'phone')
            ->where('is_active', true)
            ->orderBy('ragione_sociale')
            ->get();

        return response()->json($clients);
    }

    public function show(string $id)
    {
        $contract = Contract::with('client')->findOrFail($id);
        return response()->json($contract);
    }

    public function update(Request $request, string $id)
    {
        $contract = Contract::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:servizio,fornitura,partnership,lavoro',
            'status' => 'sometimes|in:bozza,attivo,in_scadenza,scaduto',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'renewal_type' => 'sometimes|in:manuale,automatico,nessuno',
            'amount' => 'nullable|numeric|min:0',
            'file_path' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $contract->update($validated);
        return response()->json($contract->load('client'));
    }

    public function destroy(string $id)
    {
        $contract = Contract::findOrFail($id);
        $contract->delete();
        return response()->json(['message' => 'Contratto eliminato con successo']);
    }

    public function stats()
    {
        $stats = [
            'total' => Contract::count(),
            'attivo' => Contract::active()->count(),
            'in_scadenza' => Contract::expiring(30)->count(),
            'scaduto' => Contract::where('status', 'scaduto')->count(),
            'bozza' => Contract::where('status', 'bozza')->count(),
        ];
        return response()->json($stats);
    }
}
