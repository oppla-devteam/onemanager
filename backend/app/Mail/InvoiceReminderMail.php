<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public int $daysLate;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, int $daysLate)
    {
        $this->invoice = $invoice;
        $this->daysLate = $daysLate;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sollecito Pagamento Fattura {$this->invoice->numero_fattura}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-reminder',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'daysLate' => $this->daysLate,
                'totalAmount' => number_format($this->invoice->totale, 2, ',', '.'),
                'dueDate' => $this->invoice->data_scadenza->format('d/m/Y'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Attach PDF if available
        if ($this->invoice->pdf_file_path && \Storage::disk('local')->exists($this->invoice->pdf_file_path)) {
            $attachments[] = Attachment::fromStorageDisk('local', $this->invoice->pdf_file_path)
                ->as("Fattura_{$this->invoice->numero_fattura}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
