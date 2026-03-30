<?php

namespace App\Observers;

use App\Models\OnboardingSession;
// NOTA: Le email di benvenuto/follow-up NON vengono più inviate da OneManager.
// L'account partner viene creato su oppla.delivery che gestisce l'invio delle email di benvenuto.
// use App\Jobs\SendOnboardingFollowUpEmail;
// use App\Jobs\SendOnboardingWelcomeEmail;
use App\Jobs\SendAppointmentConfirmationEmail;

class OnboardingSessionObserver
{
    /**
     * Handle the OnboardingSession "updated" event.
     */
    public function updated(OnboardingSession $session)
    {
        // NOTA: Le email di follow-up NON vengono più inviate da OneManager.
        // oppla.delivery gestisce le comunicazioni con i partner dopo la creazione dell'account.
        if ($session->isDirty('status') && $session->status === 'completed') {
            \Log::info('Onboarding completato - Account partner creato su oppla.delivery', [
                'session_id' => $session->id,
                'client_id' => $session->client_id,
            ]);
            // Email di benvenuto e follow-up inviate da oppla.delivery, non da OneManager
        }

        // Quando viene fissato un appuntamento, invia email di conferma
        // NOTA: Questa email viene mantenuta perché è una comunicazione interna di scheduling
        if ($session->isDirty('scheduled_at') && $session->scheduled_at) {
            \Log::info('Appuntamento fissato - Invio email conferma', [
                'session_id' => $session->id,
                'scheduled_at' => $session->scheduled_at,
            ]);

            if (config('queue.default') === 'sync' || $this->isQueueAvailable()) {
                try {
                    SendAppointmentConfirmationEmail::dispatch($session);
                } catch (\Exception $e) {
                    \Log::warning('Impossibile inviare email conferma appuntamento (queue non disponibile)', [
                        'session_id' => $session->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle the OnboardingSession "created" event.
     */
    public function created(OnboardingSession $session)
    {
        \Log::info('Nuova sessione onboarding creata', [
            'session_id' => $session->id,
            'client_id' => $session->client_id,
            'type' => $session->type ?? 'standard',
        ]);

        // NOTA: Le email di benvenuto NON vengono più inviate da OneManager.
        // L'account partner viene creato su oppla.delivery che invia automaticamente
        // l'email di benvenuto con il link per impostare la password.
        \Log::info('Email di benvenuto gestita da oppla.delivery (non da OneManager)', [
            'session_id' => $session->id,
        ]);
    }

    /**
     * Check if queue driver is available
     */
    private function isQueueAvailable(): bool
    {
        $driver = config('queue.default');
        
        if ($driver === 'redis') {
            try {
                \Illuminate\Support\Facades\Redis::ping();
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        
        if ($driver === 'database') {
            return true;
        }
        
        return $driver === 'sync';
    }
}
