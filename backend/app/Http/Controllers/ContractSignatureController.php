<?php

namespace App\Http\Controllers;

use App\Models\ContractSignature;
use App\Models\Contract;
use App\Services\ContractSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractSignatureController extends Controller
{
    public function __construct(
        private ContractSignatureService $signatureService
    ) {}

    /**
     * Visualizza pagina firma (pubblica, no auth)
     */
    public function showSignaturePage(string $token)
    {
        $signature = ContractSignature::where('signature_token', $token)->firstOrFail();

        if (!$signature->isTokenValid()) {
            return response()->json([
                'error' => 'Token scaduto o non valido'
            ], 403);
        }

        // Marca come visualizzato
        $signature->markAsViewed();

        $contract = $signature->contract()->with('template')->first();

        return response()->json([
            'signature' => $signature->makeVisible('signature_token'),
            'contract' => $contract,
            'signer' => [
                'name' => $signature->signer_name,
                'email' => $signature->signer_email,
                'role' => $signature->signer_role,
            ],
        ]);
    }

    /**
     * Richiedi OTP per firma
     */
    public function requestOtp(Request $request, string $token)
    {
        $signature = ContractSignature::where('signature_token', $token)->firstOrFail();

        if (!$signature->isTokenValid()) {
            return response()->json([
                'error' => 'Token scaduto o non valido'
            ], 403);
        }

        if ($signature->status === 'signed') {
            return response()->json([
                'error' => 'Documento già firmato'
            ], 422);
        }

        try {
            $this->signatureService->sendOtp($signature);

            return response()->json([
                'message' => 'Codice OTP inviato via email',
                'email' => substr($signature->signer_email, 0, 3) . '***@***.' . 
                          substr(strrchr($signature->signer_email, '.'), 1)
            ]);
        } catch (\Exception $e) {
            Log::error('Errore invio OTP', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Errore invio OTP',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Processa firma
     */
    public function sign(Request $request, string $token)
    {
        $signature = ContractSignature::where('signature_token', $token)->firstOrFail();

        if (!$signature->isTokenValid()) {
            return response()->json([
                'error' => 'Token scaduto o non valido'
            ], 403);
        }

        if ($signature->status === 'signed') {
            return response()->json([
                'error' => 'Documento già firmato'
            ], 422);
        }

        $validated = $request->validate([
            'signature_data' => 'required|string', // Base64 della firma
            'otp_code' => 'required|string|size:6',
        ]);

        try {
            // Processa firma (il service gestisce OTP internamente)
            $this->signatureService->processSignature(
                $signature,
                $validated['signature_data'],
                'drawn', // Tipo firma disegnata su canvas
                $validated['otp_code'] // OTP code
            );

            return response()->json([
                'message' => 'Firma applicata con successo',
                'contract' => $signature->contract,
                'all_signed' => $signature->contract->isFullySigned(),
            ]);
        } catch (\Exception $e) {
            Log::error('Errore firma contratto', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Errore durante la firma',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Rifiuta firma
     */
    public function decline(Request $request, string $token)
    {
        $signature = ContractSignature::where('signature_token', $token)->firstOrFail();

        if (!$signature->isTokenValid()) {
            return response()->json([
                'error' => 'Token scaduto o non valido'
            ], 403);
        }

        if ($signature->status === 'signed') {
            return response()->json([
                'error' => 'Documento già firmato, impossibile rifiutare'
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->signatureService->declineSignature($signature, $validated['reason']);

            return response()->json([
                'message' => 'Firma rifiutata'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore rifiuto firma',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Richiedi nuova firma (admin)
     */
    public function requestResign(ContractSignature $signature)
    {
        try {
            $this->signatureService->requestResign($signature);

            return response()->json([
                'message' => 'Nuova richiesta firma inviata'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore richiesta firma',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Lista firme di un contratto (admin)
     */
    public function index(Contract $contract)
    {
        $signatures = $contract->signatures()
            ->orderBy('signing_order')
            ->get();

        return response()->json($signatures);
    }

    /**
     * Aggiungi firmatario a contratto esistente (admin)
     */
    public function store(Request $request, Contract $contract)
    {
        if (!in_array($contract->status, ['draft', 'pending_review'])) {
            return response()->json([
                'error' => 'Impossibile aggiungere firmatari a questo contratto'
            ], 422);
        }

        $validated = $request->validate([
            'signer_name' => 'required|string',
            'signer_email' => 'required|email',
            'signer_phone' => 'nullable|string',
            'signer_role' => 'required|string',
            'signing_order' => 'required|integer|min:1',
        ]);

        $signature = $contract->signatures()->create($validated + [
            'status' => 'pending',
        ]);

        return response()->json($signature, 201);
    }

    /**
     * Elimina firmatario (solo se non ancora firmato)
     */
    public function destroy(ContractSignature $signature)
    {
        if ($signature->status === 'signed') {
            return response()->json([
                'error' => 'Impossibile eliminare una firma già apposta'
            ], 422);
        }

        $contract = $signature->contract;
        
        if (!in_array($contract->status, ['draft', 'pending_review'])) {
            return response()->json([
                'error' => 'Impossibile eliminare firmatari da questo contratto'
            ], 422);
        }

        $signature->delete();

        return response()->json([
            'message' => 'Firmatario eliminato'
        ]);
    }
}
