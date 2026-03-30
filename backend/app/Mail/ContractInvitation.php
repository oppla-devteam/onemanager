<?php

namespace App\Mail;

use App\Models\ContractSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContractSignature $signature,
        public string $signatureUrl
    ) {}

    public function build()
    {
        $contract = $this->signature->contract;
        
        return $this->subject("Richiesta firma: {$contract->subject}")
            ->view('emails.contract-invitation')
            ->with([
                'signer_name' => $this->signature->signer_name,
                'contract_number' => $contract->contract_number,
                'contract_subject' => $contract->subject,
                'client_name' => $contract->client_name,
                'signature_url' => $this->signatureUrl,
                'expires_at' => $this->signature->token_expires_at,
            ]);
    }
}
