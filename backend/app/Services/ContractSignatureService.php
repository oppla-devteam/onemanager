<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractSignature;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ContractInvitation;
use App\Mail\ContractSignedNotification;
use App\Mail\ContractOtpMail;

class ContractSignatureService
{
    public function __construct(
        private ContractPdfService $pdfService
    ) {}

    /**
     * Invia invito firma a tutti i firmatari
     */
    public function sendSignatureInvitations(Contract $contract): void
    {
        $contract->signatures()->each(function ($signature) use ($contract) {
            $this->sendSignatureInvitation($signature);
        });

        $contract->update([
            'status' => 'sent_to_client',
            'sent_at' => now(),
        ]);

        $contract->logHistory('sent_to_sign', null, 'sent_to_client', null, 'Inviti firma inviati');
    }

    /**
     * Invia invito firma singolo
     */
    public function sendSignatureInvitation(ContractSignature $signature, ?string $customMessage = null): void
    {
        try {
            $signatureUrl = $this->generateSignatureUrl($signature);
            
            Mail::to($signature->signer_email)->send(
                new ContractInvitation($signature, $signatureUrl, $customMessage)
            );

            $signature->update([
                'status' => 'invited',
                'invited_at' => now(),
            ]);

            Log::info('Invito firma inviato', [
                'contract_id' => $signature->contract_id,
                'signature_id' => $signature->id,
                'email' => $signature->signer_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Errore invio invito firma', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Genera URL per firma
     */
    public function generateSignatureUrl(ContractSignature $signature): string
    {
        if (!$signature->isTokenValid()) {
            $signature->regenerateToken();
        }

        return url("/contracts/sign/{$signature->signature_token}");
    }

    /**
     * Processa firma contratto
     */
    public function processSignature(
        ContractSignature $signature,
        string $signatureData,
        string $signatureType,
        string $otp
    ): void {
        // Verifica OTP
        if (!$signature->verifyOTP($otp)) {
            throw new \Exception('Codice OTP non valido o scaduto');
        }

        // Marca come firmato
        $signature->markAsSigned($signatureData, $signatureType);

        $contract = $signature->contract;
        
        // Log storico
        $contract->logHistory(
            'signature_added',
            null,
            null,
            ['signer' => $signature->signer_name, 'role' => $signature->signer_role],
            "Firma aggiunta da {$signature->signer_name}"
        );

        // Verifica se tutte le firme sono completate
        if ($contract->isFullySigned()) {
            $this->completeContract($contract);
        } else {
            // Aggiorna stato a parzialmente firmato
            $contract->update(['status' => 'partially_signed']);
            
            // Invia invito al prossimo firmatario
            $nextSigner = $contract->getNextSigner();
            if ($nextSigner) {
                $this->sendSignatureInvitation($nextSigner);
            }
        }

        // Notifica firma completata
        $this->notifySignatureCompleted($signature);
    }

    /**
     * Completa contratto quando tutte le firme sono raccolte
     */
    private function completeContract(Contract $contract): void
    {
        // Genera PDF firmato
        $this->pdfService->generateSignedPdf($contract);

        // Aggiorna stato
        $contract->update([
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        $contract->logHistory('fully_signed', 'partially_signed', 'signed', null, 'Tutte le firme completate');

        // Notifica completamento
        $this->notifyContractSigned($contract);
    }

    /**
     * Invia OTP per verifica firma
     */
    public function sendOtp(ContractSignature $signature): string
    {
        $otp = $signature->generateOTP();

        try {
            Mail::to($signature->signer_email)->send(
                new ContractOtpMail($signature, $otp)
            );

            Log::info('OTP inviato', [
                'signature_id' => $signature->id,
                'email' => $signature->signer_email,
            ]);

            return $otp;
        } catch (\Exception $e) {
            Log::error('Errore invio OTP', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Rifiuta firma
     */
    public function declineSignature(ContractSignature $signature, string $reason): void
    {
        $signature->markAsDeclined($reason);

        $contract = $signature->contract;
        $contract->update(['status' => 'cancelled']);

        $contract->logHistory(
            'signature_declined',
            null,
            'cancelled',
            ['signer' => $signature->signer_name, 'reason' => $reason],
            "Firma rifiutata da {$signature->signer_name}"
        );

        // Notifica rifiuto
        $this->notifySignatureDeclined($signature, $reason);
    }

    /**
     * Notifica firma completata
     */
    private function notifySignatureCompleted(ContractSignature $signature): void
    {
        try {
            // Notifica al creatore del contratto
            $creator = $signature->contract->creator;
            if ($creator && $creator->email) {
                Mail::to($creator->email)->send(
                    new ContractSignedNotification($signature->contract, $signature)
                );
            }
        } catch (\Exception $e) {
            Log::error('Errore notifica firma completata', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifica contratto completamente firmato
     */
    private function notifyContractSigned(Contract $contract): void
    {
        try {
            // Notifica a tutti i firmatari
            $contract->signatures->each(function ($signature) use ($contract) {
                Mail::to($signature->signer_email)->send(
                    new ContractSignedNotification($contract, $signature, true)
                );
            });

            // Notifica al creatore
            if ($contract->creator && $contract->creator->email) {
                Mail::to($contract->creator->email)->send(
                    new ContractSignedNotification($contract, null, true)
                );
            }
        } catch (\Exception $e) {
            Log::error('Errore notifica contratto firmato', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notifica firma rifiutata
     */
    private function notifySignatureDeclined(ContractSignature $signature, string $reason): void
    {
        try {
            $creator = $signature->contract->creator;
            if ($creator && $creator->email) {
                // TODO: Creare mail specifica per rifiuto
                Log::info('Firma rifiutata', [
                    'contract_id' => $signature->contract_id,
                    'signer' => $signature->signer_name,
                    'reason' => $reason,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Errore notifica firma rifiutata', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Richiedi nuova firma (se scaduta o rifiutata)
     */
    public function requestResign(ContractSignature $signature): void
    {
        $signature->regenerateToken();
        $signature->update([
            'status' => 'pending',
            'declined_at' => null,
            'decline_reason' => null,
        ]);

        $this->sendSignatureInvitation($signature);
    }
}
