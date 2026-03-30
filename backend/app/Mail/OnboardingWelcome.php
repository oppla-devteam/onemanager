<?php

namespace App\Mail;

use App\Models\OnboardingSession;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public OnboardingSession $session;
    public ?Client $client;

    /**
     * Create a new message instance.
     */
    public function __construct(OnboardingSession $session)
    {
        $this->session = $session;
        $this->client = $session->client;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Benvenuto in OPPLA! 🎉 Il tuo percorso di onboarding è iniziato',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.onboarding-welcome',
            with: [
                'clientName' => $this->client?->ragione_sociale ?? 'Partner',
                'sessionId' => $this->session->id,
                'currentStep' => $this->session->current_step ?? 'owner',
                'supportEmail' => config('mail.support_email', 'supporto@oppla.it'),
                'supportPhone' => config('mail.support_phone', '+39 050 123 4567'),
            ],
        );
    }
}
