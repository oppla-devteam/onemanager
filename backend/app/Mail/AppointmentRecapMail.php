<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentRecapMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public ?string $notes = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Riepilogo Appuntamento - Condizioni Servizio OPPLA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-recap',
            with: [
                'clientName' => $this->client->ragione_sociale ?? 'Partner',
                'feeMensile' => $this->client->fee_mensile,
                'feeOrdine' => $this->client->fee_ordine,
                'feeConsegnaBase' => $this->client->fee_consegna_base,
                'feeConsegnaKm' => $this->client->fee_consegna_km,
                'abbonamentoMensile' => $this->client->abbonamento_mensile,
                'hasDomain' => $this->client->has_domain ?? false,
                'hasPos' => $this->client->has_pos ?? false,
                'hasDelivery' => $this->client->has_delivery ?? false,
                'notes' => $this->notes,
                'supportEmail' => config('mail.support_email', 'supporto@oppla.it'),
                'supportPhone' => config('mail.support_phone', '+39 050 123 4567'),
            ],
        );
    }
}
