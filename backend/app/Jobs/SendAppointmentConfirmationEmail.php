<?php

namespace App\Jobs;

use App\Mail\AppointmentConfirmation;
use App\Models\OnboardingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAppointmentConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public OnboardingSession $session;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(OnboardingSession $session)
    {
        $this->session = $session;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $session = $this->session;
        $client = $session->client;

        if (!$client) {
            Log::warning('SendAppointmentConfirmationEmail: Client non trovato', [
                'session_id' => $session->id,
            ]);
            return;
        }

        if (!$session->scheduled_at) {
            Log::warning('SendAppointmentConfirmationEmail: Data appuntamento mancante', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $recipientEmail = $client->email;
        
        if (!$recipientEmail) {
            Log::warning('SendAppointmentConfirmationEmail: Email client mancante', [
                'session_id' => $session->id,
                'client_id' => $client->id,
            ]);
            return;
        }

        try {
            Mail::to($recipientEmail)->send(new AppointmentConfirmation($session));

            Log::info('Email conferma appuntamento inviata', [
                'session_id' => $session->id,
                'client_id' => $client->id,
                'recipient' => $recipientEmail,
                'scheduled_at' => $session->scheduled_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Errore invio email conferma appuntamento', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAppointmentConfirmationEmail fallito definitivamente', [
            'session_id' => $this->session->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
