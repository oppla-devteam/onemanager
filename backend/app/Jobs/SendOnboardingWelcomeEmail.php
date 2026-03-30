<?php

namespace App\Jobs;

use App\Mail\OnboardingWelcome;
use App\Models\OnboardingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @deprecated Non più utilizzato. L'email di benvenuto viene inviata da oppla.delivery
 * quando viene creato l'account partner sulla piattaforma esterna.
 * 
 * OneManager crea l'account partner su oppla.delivery via API, e oppla.delivery
 * gestisce l'invio dell'email di benvenuto con il link per impostare la password.
 * 
 * Questa classe è mantenuta per retrocompatibilità ma non viene più dispatchata.
 */
class SendOnboardingWelcomeEmail implements ShouldQueue
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
            Log::warning('SendOnboardingWelcomeEmail: Client non trovato', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $recipientEmail = $client->email;
        
        if (!$recipientEmail) {
            Log::warning('SendOnboardingWelcomeEmail: Email client mancante', [
                'session_id' => $session->id,
                'client_id' => $client->id,
            ]);
            return;
        }

        try {
            Mail::to($recipientEmail)->send(new OnboardingWelcome($session));

            Log::info('Email di benvenuto onboarding inviata', [
                'session_id' => $session->id,
                'client_id' => $client->id,
                'recipient' => $recipientEmail,
            ]);
        } catch (\Exception $e) {
            Log::error('Errore invio email benvenuto onboarding', [
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
        Log::error('SendOnboardingWelcomeEmail fallito definitivamente', [
            'session_id' => $this->session->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
