<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ContractSendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Contract $contract,
        public ?string $customMessage = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Contratto {$this->contract->contract_number} - {$this->contract->subject}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-send',
            with: [
                'contract' => $this->contract,
                'customMessage' => $this->customMessage,
                'signatureUrl' => $this->getSignatureUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        // Allega il PDF del contratto se esiste
        if ($this->contract->pdf_path && Storage::exists($this->contract->pdf_path)) {
            $attachments[] = Attachment::fromStorage($this->contract->pdf_path)
                ->as("Contratto_{$this->contract->contract_number}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }

    /**
     * Ottieni URL per la firma
     */
    private function getSignatureUrl(): ?string
    {
        $signature = $this->contract->signatures()
            ->where('status', 'pending')
            ->first();

        if ($signature) {
            return url("/api/contracts/sign/{$signature->token}");
        }

        return null;
    }
}
