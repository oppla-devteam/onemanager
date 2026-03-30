<?php

namespace App\Mail;

use App\Models\OnboardingSession;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class AppointmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public OnboardingSession $session;
    public ?Client $client;
    public Carbon $appointmentDate;

    /**
     * Create a new message instance.
     */
    public function __construct(OnboardingSession $session)
    {
        $this->session = $session;
        $this->client = $session->client;
        $this->appointmentDate = Carbon::parse($session->scheduled_at);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $formattedDate = $this->appointmentDate->locale('it')->isoFormat('D MMMM YYYY [alle] HH:mm');
        
        return new Envelope(
            subject: "📅 Appuntamento confermato: {$formattedDate}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-confirmation',
            with: [
                'clientName' => $this->client?->ragione_sociale ?? 'Partner',
                'appointmentDate' => $this->appointmentDate->locale('it')->isoFormat('dddd D MMMM YYYY'),
                'appointmentTime' => $this->appointmentDate->format('H:i'),
                'sessionId' => $this->session->id,
                'notes' => $this->session->temp_data['appointment_notes'] ?? null,
                'supportEmail' => config('mail.support_email', 'supporto@oppla.it'),
                'supportPhone' => config('mail.support_phone', '+39 050 123 4567'),
                'rescheduleUrl' => config('app.url') . '/onboarding/reschedule/' . $this->session->id,
            ],
        );
    }
}
