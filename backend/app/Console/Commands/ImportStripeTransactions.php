<?php

namespace App\Console\Commands;

use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportStripeTransactions extends Command
{
    protected $signature = 'stripe:import-transactions 
                            {--month= : Month to import (format: YYYY-MM)}
                            {--days=30 : Number of days to import if month not specified}
                            {--full-sync : Force full sync - re-import and update all transactions}';

    protected $description = 'Import Stripe transactions and reconcile with invoices. Use --full-sync for initial import';

    public function handle(StripeService $stripeService): int
    {
        $fullSync = $this->option('full-sync');
        
        if ($fullSync) {
            $this->warn('⚠️  SYNC COMPLETO FORZATO - Tutte le transazioni verranno aggiornate');
        } else {
            $this->info('🔄 Importazione incrementale transazioni Stripe...');
        }

        // Determina il periodo
        if ($month = $this->option('month')) {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            $this->info("📅 Periodo: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}");
        } else {
            $days = (int) $this->option('days');
            $endDate = now();
            $startDate = now()->subDays($days);
            $this->info("📅 Ultimi {$days} giorni");
        }

        // Importa transazioni
        $this->info($fullSync ? '💳 Sync completo in corso...' : '💳 Importazione transazioni...');
        $this->newLine();
        $result = $stripeService->importTransactions($startDate, $endDate, $fullSync, $this);

        $this->newLine();
        $this->info("Transazioni importate: {$result['imported']}");
        if (isset($result['updated']) && $result['updated'] > 0) {
            $this->info("🔄 Transazioni aggiornate: {$result['updated']}");
        }
        if (isset($result['skipped']) && $result['skipped'] > 0) {
            $this->info("⏭️  Transazioni saltate (già esistenti): {$result['skipped']}");
        }
        $this->info("💰 Saldo disponibile: €" . number_format($result['balance'], 2));

        if (count($result['errors']) > 0) {
            $this->warn("⚠️  Errori: " . count($result['errors']));
            foreach ($result['errors'] as $error) {
                $this->error("  • {$error}");
            }
        }

        // Riconciliazione automatica
        $this->newLine();
        $this->info('🔗 Riconciliazione con fatture...');
        $reconciled = $stripeService->reconcileTransactions($startDate, $endDate);
        $this->info("Fatture riconciliate: {$reconciled}");

        // Sincronizza Application Fees (commissioni riscosse)
        $this->newLine();
        $this->info('💰 Sincronizzazione commissioni riscosse (Application Fees)...');
        $this->info("📅 Periodo: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}");
        try {
            $feesResult = $stripeService->syncApplicationFees($startDate, $endDate);
            $this->info("Commissioni importate: {$feesResult['imported']}");
            if ($feesResult['skipped'] > 0) {
                $this->info("⏭️  Commissioni saltate (già esistenti): {$feesResult['skipped']}");
            }
            if ($feesResult['errors'] > 0) {
                $this->warn("⚠️  Errori sincronizzazione commissioni: {$feesResult['errors']}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Errore sincronizzazione commissioni: " . $e->getMessage());
        }

        // Esporta CSV
        $this->newLine();
        if ($this->confirm('Vuoi esportare l\'estratto conto in CSV?', true)) {
            $filename = $stripeService->exportStatement($startDate, $endDate);
            $this->info("📊 Estratto conto esportato: storage/app/exports/{$filename}");
        }

        $this->newLine();
        $this->info('✨ Importazione completata!');

        return Command::SUCCESS;
    }
}
