<?php

namespace App\Mail;

use App\Models\ContractSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContractSignature $signature,
        public string $otp
    ) {}

    public function build()
    {
        return $this->subject("Codice verifica firma contratto")
            ->view('emails.contract-otp')
            ->with([
                'signer_name' => $this->signature->signer_name,
                'otp' => $this->otp,
                'expires_at' => $this->signature->otp_expires_at,
                'contract_number' => $this->signature->contract->contract_number,
            ]);
    }
}
