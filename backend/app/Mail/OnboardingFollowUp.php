<?php

namespace App\Mail;

use App\Models\OnboardingSession;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingFollowUp extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $client;

    /**
     * Create a new message instance.
     */
    public function __construct(OnboardingSession $session, Client $client)
    {
        $this->session = $session;
        $this->client = $client;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Come sta andando con OPPLA? 🚀')
                    ->view('emails.onboarding-follow-up')
                    ->with([
                        'clientName' => $this->client->ragione_sociale,
                        'sessionType' => $this->session->type,
                        'completedAt' => $this->session->completed_at,
                    ]);
    }
}
