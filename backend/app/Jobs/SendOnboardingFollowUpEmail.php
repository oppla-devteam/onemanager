<?php

namespace App\Jobs;

use App\Models\OnboardingSession;
use App\Mail\OnboardingFollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * @deprecated Non più utilizzato. Le email di follow-up vengono gestite da oppla.delivery.
 * 
 * OneManager crea l'account partner su oppla.delivery via API, e oppla.delivery
 * gestisce tutte le comunicazioni email con i partner.
 * 
 * Questa classe è mantenuta per retrocompatibilità ma non viene più dispatchata.
 */
class SendOnboardingFollowUpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $session;

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
        $client = $this->session->client;

        if (!$client || !$client->email) {
            \Log::warning('Cliente senza email - skip follow-up', [
                'session_id' => $this->session->id,
                'client_id' => $this->session->client_id,
            ]);
            return;
        }

        try {
            Mail::to($client->email)
                ->send(new OnboardingFollowUp($this->session, $client));

            \Log::info('Email follow-up onboarding inviata', [
                'session_id' => $this->session->id,
                'client_email' => $client->email,
            ]);

            // Aggiorna ultimo contatto
            $client->update(['last_contact_date' => now()]);

        } catch (\Exception $e) {
            \Log::error('Errore invio email follow-up', [
                'session_id' => $this->session->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
