<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Fatturazione differita mensile - 1° del mese alle 02:00
        $schedule->command('invoices:generate-monthly')
            ->monthlyOn(1, '02:00')
            ->timezone('Europe/Rome')
            ->name('monthly-deferred-invoicing')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Fatturazione differita mensile completata con successo');
            })
            ->onFailure(function () {
                \Log::error('Fatturazione differita mensile fallita');
            });

        // Fatturazione mensile automatica (vecchio job) - 1° del mese alle 02:00
        $schedule->job(new \App\Jobs\ProcessMonthlyInvoicing())
            ->monthlyOn(1, '02:00')
            ->timezone('Europe/Rome')
            ->name('monthly-invoicing')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Fatturazione mensile completata con successo');
            })
            ->onFailure(function () {
                \Log::error('Fatturazione mensile fallita');
            });

        // Import estratti conto bancari - 10 del mese alle 08:00
        $schedule->command('bank:import-statements')
            ->monthlyOn(10, '08:00')
            ->timezone('Europe/Rome');

        // Import transazioni Stripe - ogni giorno alle 06:00
        $schedule->command('stripe:import-transactions --days=1')
            ->dailyAt('06:00')
            ->timezone('Europe/Rome')
            ->name('stripe-import')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Import transazioni Stripe completato');
            })
            ->onFailure(function () {
                \Log::error('Import transazioni Stripe fallito');
            });

        // Sincronizzazione zone di consegna Oppla - ogni giorno alle 03:00
        $schedule->command('oppla:sync-zones')
            ->dailyAt('03:00')
            ->timezone('Europe/Rome')
            ->name('oppla-sync-zones')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Sincronizzazione zone di consegna Oppla completata');
            })
            ->onFailure(function () {
                \Log::error('Sincronizzazione zone di consegna Oppla fallita');
            });

        // 🔄 Sincronizzazione Partners Oppla - ogni notte alle 02:00
        $schedule->command('oppla:sync-partners')
            ->dailyAt('02:00')
            ->timezone('Europe/Rome')
            ->name('oppla-partners-sync')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('[OpplaSync] Sincronizzazione notturna completata con successo');
            })
            ->onFailure(function () {
                \Log::error('[OpplaSync] Sincronizzazione notturna fallita');
            });

        // 📧 Solleciti fatture scadute - ogni lunedì alle 10:00
        $schedule->command('invoices:send-reminders')
            ->weekly()
            ->mondays()
            ->at('10:00')
            ->timezone('Europe/Rome')
            ->name('invoice-reminders')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Solleciti fatture inviati con successo');
            })
            ->onFailure(function () {
                \Log::error('Invio solleciti fatture fallito');
            });

        // 📊 Report mensili partner - 1° del mese alle 09:00
        $schedule->command('partners:send-monthly-reports')
            ->monthlyOn(1, '09:00')
            ->timezone('Europe/Rome')
            ->name('partner-monthly-reports')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Report mensili partner inviati con successo');
            })
            ->onFailure(function () {
                \Log::error('Invio report mensili partner fallito');
            });

        // 🔄 Sincronizzazione fatture da Fatture in Cloud - ogni giorno alle 07:00
        $schedule->command('fic:sync-invoices --from=-30days')
            ->dailyAt('07:00')
            ->timezone('Europe/Rome')
            ->name('fic-sync-invoices')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Sincronizzazione fatture FIC completata');
            })
            ->onFailure(function () {
                \Log::error('Sincronizzazione fatture FIC fallita');
            });

        // 📥 Sincronizzazione fatture passive (acquisti) da FIC - ogni giorno alle 07:30
        $schedule->command('fic:sync-passive-invoices')
            ->dailyAt('07:30')
            ->timezone('Europe/Rome')
            ->name('fic-sync-passive-invoices')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Sincronizzazione fatture passive FIC completata');
            })
            ->onFailure(function () {
                \Log::error('Sincronizzazione fatture passive FIC fallita');
            });

        // Verifica scadenze contratti - ogni giorno alle 09:00
        $schedule->command('contracts:expiration-alerts --days=30')
            ->dailyAt('09:00')
            ->timezone('Europe/Rome')
            ->name('contract-expiration-alerts')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Alert scadenze contratti inviati con successo');
            })
            ->onFailure(function () {
                \Log::error('Invio alert scadenze contratti fallito');
            });

        // 🔄 Rinnovi automatici contratti - ogni giorno alle 09:30
        $schedule->command('contracts:process-renewals')
            ->dailyAt('09:30')
            ->timezone('Europe/Rome')
            ->name('contract-auto-renewals')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Rinnovi automatici contratti completati');
            })
            ->onFailure(function () {
                \Log::error('Rinnovi automatici contratti falliti');
            });

        // 🔄 Auto-riconciliazione bancaria - ogni mercoledì alle 11:00
        $schedule->command('accounting:auto-reconcile')
            ->weekly()
            ->wednesdays()
            ->at('11:00')
            ->timezone('Europe/Rome')
            ->name('auto-reconcile-banking')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Auto-riconciliazione bancaria completata');
            })
            ->onFailure(function () {
                \Log::error('Auto-riconciliazione bancaria fallita');
            });

        // 📧 Email automation - processa sequenze ogni ora
        $schedule->command('email:process-sequences')
            ->hourly()
            ->timezone('Europe/Rome')
            ->name('process-email-sequences')
            ->withoutOverlapping()
            ->onSuccess(function () {
                \Log::info('Email sequences processate con successo');
            })
            ->onFailure(function () {
                \Log::error('Errore nel processare email sequences');
            });

        // Solleciti fatture scadute - ogni lunedì alle 10:00
        $schedule->command('invoices:send-reminders')
            ->weekly()
            ->mondays()
            ->at('10:00')
            ->timezone('Europe/Rome');

        // Backup database - ogni giorno alle 03:00
        $schedule->command('backup:run')
            ->dailyAt('03:00')
            ->timezone('Europe/Rome');

        // Pulizia cestino - ogni giorno alle 04:00
        $schedule->command('trash:purge')
            ->dailyAt('04:00')
            ->timezone('Europe/Rome')
            ->name('trash-purge')
            ->withoutOverlapping();

        // Pulizia log vecchi - ogni domenica
        $schedule->command('logs:clean')
            ->weekly()
            ->sundays()
            ->at('04:00');

        // ===== SINCRONIZZAZIONE OPPLA (ORDINI E CONSEGNE) - 2 VOLTE AL GIORNO =====

        // Sync 1: Notte ore 02:00 - sync completo ordini e consegne da OPPLA
        $schedule->command('oppla:sync-orders')
            ->dailyAt('02:00')
            ->timezone('Europe/Rome')
            ->name('oppla-orders-sync-night')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('[OpplaSync] Sincronizzazione notturna ordini completata');
            })
            ->onFailure(function () {
                Log::error('[OpplaSync] Sincronizzazione notturna ordini fallita');
            });

        // Sync 2: Pomeriggio ore 15:30 - sync incrementale ordini e consegne da OPPLA
        $schedule->command('oppla:sync-orders')
            ->dailyAt('15:30')
            ->timezone('Europe/Rome')
            ->name('oppla-orders-sync-afternoon')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('[OpplaSync] Sincronizzazione pomeridiana ordini completata');
            })
            ->onFailure(function () {
                Log::error('[OpplaSync] Sincronizzazione pomeridiana ordini fallita');
            });

        // Sincronizzazione dati da piattaforma OPPLA - ogni ora
        $schedule->command('oppla:sync-data')
            ->hourly()
            ->withoutOverlapping();

        // Aggiornamento KPI dashboard - ogni 15 minuti
        $schedule->command('dashboard:update-kpi')
            ->everyFifteenMinutes()
            ->withoutOverlapping();

        // 🚴 Sincronizzazione Rider da Tookan - ogni 2 minuti per real-time
        $schedule->command('tookan:sync-riders')
            ->everyTwoMinutes()
            ->timezone('Europe/Rome')
            ->name('tookan-rider-sync')
            ->withoutOverlapping(2) // Prevent overlapping runs (max 2 min wait)
            ->onOneServer() // Only run on one server in load-balanced setup
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('[TookanSync] Rider sync completed successfully');
            })
            ->onFailure(function () {
                Log::error('[TookanSync] Rider sync failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
