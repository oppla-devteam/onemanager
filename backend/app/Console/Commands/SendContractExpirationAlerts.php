<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractExpirationAlert;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendContractExpirationAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:expiration-alerts 
                            {--days=30 : Number of days before expiration to send alert}
                            {--dry-run : Preview alerts without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send alerts for contracts expiring within specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Cercando contratti in scadenza entro {$days} giorni...");

        $today = Carbon::today();
        $expirationDate = Carbon::today()->addDays($days);

        // Trova contratti attivi in scadenza
        $expiringContracts = Contract::query()
            ->whereIn('status', ['active', 'signed'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $expirationDate)
            ->with('client')
            ->orderBy('end_date')
            ->get();

        if ($expiringContracts->isEmpty()) {
            $this->info('✅ Nessun contratto in scadenza trovato.');
            return Command::SUCCESS;
        }

        $this->info("📋 Trovati {$expiringContracts->count()} contratti in scadenza:");
        $this->newLine();

        // Mostra tabella riepilogativa
        $tableData = $expiringContracts->map(function ($contract) use ($today) {
            $daysLeft = $today->diffInDays(Carbon::parse($contract->end_date), false);
            return [
                'ID' => $contract->id,
                'Titolo' => substr($contract->title ?? 'N/A', 0, 30),
                'Cliente' => substr($contract->client?->ragione_sociale ?? 'N/A', 0, 25),
                'Scadenza' => Carbon::parse($contract->end_date)->format('d/m/Y'),
                'Giorni' => $daysLeft,
            ];
        })->toArray();

        $this->table(['ID', 'Titolo', 'Cliente', 'Scadenza', 'Giorni'], $tableData);
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔶 Dry run mode - nessuna notifica inviata');
            return Command::SUCCESS;
        }

        // Trova utenti admin/manager da notificare
        $notifiableUsers = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super-admin', 'manager', 'account_manager']);
            })
            ->get();

        if ($notifiableUsers->isEmpty()) {
            $this->warn('⚠️ Nessun utente admin/manager trovato per le notifiche');
            return Command::SUCCESS;
        }

        $this->info("📧 Invio notifiche a {$notifiableUsers->count()} utenti...");

        // Raggruppa per urgenza
        $urgent = $expiringContracts->filter(fn($c) => Carbon::parse($c->end_date)->diffInDays($today) <= 7);
        $soon = $expiringContracts->filter(fn($c) => Carbon::parse($c->end_date)->diffInDays($today) > 7 && Carbon::parse($c->end_date)->diffInDays($today) <= 14);
        $upcoming = $expiringContracts->filter(fn($c) => Carbon::parse($c->end_date)->diffInDays($today) > 14);

        $sentCount = 0;

        try {
            foreach ($notifiableUsers as $user) {
                Notification::send($user, new ContractExpirationAlert(
                    urgent: $urgent,
                    soon: $soon,
                    upcoming: $upcoming
                ));
                $sentCount++;
            }

            Log::info('Contract expiration alerts sent', [
                'contracts_count' => $expiringContracts->count(),
                'users_notified' => $sentCount,
                'urgent' => $urgent->count(),
                'soon' => $soon->count(),
                'upcoming' => $upcoming->count(),
            ]);

            $this->info("✅ {$sentCount} notifiche inviate con successo!");
        } catch (\Exception $e) {
            Log::error('Failed to send contract expiration alerts', [
                'error' => $e->getMessage(),
            ]);
            $this->error('❌ Errore durante l\'invio: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
