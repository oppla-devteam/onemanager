<?php

namespace App\Mail;

use App\Models\Contract;
use App\Models\ContractSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractSignedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public ?ContractSignature $signature = null,
        public bool $fullySigned = false
    ) {}

    public function build()
    {
        if ($this->fullySigned) {
            return $this->subject("Contratto completamente firmato: {$this->contract->contract_number}")
                ->view('emails.contract-fully-signed')
                ->with([
                    'contract_number' => $this->contract->contract_number,
                    'contract_subject' => $this->contract->subject,
                    'signed_at' => $this->contract->signed_at,
                ]);
        }

        return $this->subject("Nuova firma apposta al contratto: {$this->contract->contract_number}")
            ->view('emails.contract-signature-added')
            ->with([
                'contract_number' => $this->contract->contract_number,
                'contract_subject' => $this->contract->subject,
                'signer_name' => $this->signature->signer_name,
                'signer_role' => $this->signature->signer_role,
                'signed_at' => $this->signature->signed_at,
            ]);
    }
}
