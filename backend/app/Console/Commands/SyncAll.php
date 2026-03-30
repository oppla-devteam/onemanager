<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncAll extends Command
{
    /**
     * Nome e firma del comando
     */
    protected $signature = 'sync:all 
                            {--force : Forza sincronizzazione anche se recente}
                            {--days=30 : Giorni per Stripe sync}
                            {--full-sync : Force full sync per Stripe}
                            {--skip-oppla : Salta sincronizzazione OPPLA}
                            {--skip-stripe : Salta sincronizzazione Stripe}
                            {--skip-fic : Salta sincronizzazione Fatture in Cloud}
                            {--only-stripe : Sincronizza SOLO Stripe}
                            {--only-oppla : Sincronizza SOLO OPPLA}
                            {--only-fic : Sincronizza SOLO Fatture in Cloud}';

    /**
     * Descrizione del comando
     */
    protected $description = 'Sincronizza TUTTO: Database OPPLA, Stripe e Fatture in Cloud in un comando';

    /**
     * Esegui il comando
     */
    public function handle()
    {
        // Gestisci flag --only-*
        $skipOppla = $this->option('skip-oppla') || $this->option('only-stripe') || $this->option('only-fic');
        $skipStripe = $this->option('skip-stripe') || $this->option('only-oppla') || $this->option('only-fic');
        $skipFic = $this->option('skip-fic') || $this->option('only-stripe') || $this->option('only-oppla');
        
        $this->info('🌐 OPPLA One Manager - Sincronizzazione Completa');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();
        
        $startTime = now();
        $results = [];

        try {
            // 1. Sincronizza Database Oppla (partners, restaurants, orders, deliveries)
            if (!$skipOppla) {
                $this->info('📊 [1/3] Database OPPLA (Partners, Restaurants, Orders)');
                $this->info('───────────────────────────────────────────────────────');
                $this->newLine();
                
                $exitCode = $this->call('sync:db', [
                    '--force' => $this->option('force')
                ]);
                
                $results['database'] = $exitCode === Command::SUCCESS;
                
                if ($results['database']) {
                    $this->info('Database OPPLA sincronizzato');
                } else {
                    $this->error('❌ Errore sincronizzazione Database OPPLA');
                }
                $this->newLine(2);
            }

            // 2. Sincronizza Transazioni Stripe
            if (!$skipStripe) {
                $this->info('💳 [2/3] Transazioni Stripe');
                $this->info('───────────────────────────────────────────────────────');
                $this->newLine();
                
                $days = (int) $this->option('days');
                $fullSync = $this->option('full-sync');
                
                if ($fullSync) {
                    $this->warn("⚠️  Full sync Stripe forzato (ultimi {$days} giorni)");
                }
                
                $params = ['--days' => $days];
                if ($fullSync) {
                    $params['--full-sync'] = true;
                }
                
                $exitCode = $this->call('stripe:import-transactions', $params);
                
                $results['stripe'] = $exitCode === Command::SUCCESS;
                
                if ($results['stripe']) {
                    $this->info('Stripe sincronizzato');
                } else {
                    $this->warn('⚠️  Errore sincronizzazione Stripe (non bloccante)');
                }
                $this->newLine(2);
            } else {
                $this->warn('⏭️  Stripe saltato');
                $this->newLine();
            }

            // 3. Sincronizza Fatture in Cloud
            if (!$skipFic) {
                $this->info('📄 [3/3] Fatture in Cloud');
                $this->info('───────────────────────────────────────────────────────');
                $this->newLine();
                
                if ($this->commandExists('sync:fatture-in-cloud')) {
                    $exitCode = $this->call('sync:fatture-in-cloud', [
                        '--force' => $this->option('force')
                    ]);
                    
                    $results['fatture_in_cloud'] = $exitCode === Command::SUCCESS;
                    
                    if ($results['fatture_in_cloud']) {
                        $this->info('Fatture in Cloud sincronizzate');
                    } else {
                        $this->warn('⚠️  Errore sincronizzazione Fatture in Cloud (non bloccante)');
                    }
                } else {
                    $this->warn('⚠️  Comando sync:fatture-in-cloud non trovato (skip)');
                    $results['fatture_in_cloud'] = null;
                }
                $this->newLine(2);
            } else {
                $this->warn('⏭️  Fatture in Cloud saltato');
                $this->newLine();
            }
            // Riepilogo finale
            $duration = $startTime->diffForHumans(now(), true);
            
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('📋 RIEPILOGO SINCRONIZZAZIONE');
            $this->info('═══════════════════════════════════════════════════════');
            $this->newLine();
            
            $tableData = [];
            if (!$skipOppla && isset($results['database'])) {
                $tableData[] = ['Database OPPLA', $this->getStatusIcon($results['database'])];
            }
            if (!$skipStripe && isset($results['stripe'])) {
                $tableData[] = ['Stripe', $this->getStatusIcon($results['stripe'])];
            }
            if (!$skipFic && isset($results['fatture_in_cloud'])) {
                $tableData[] = ['Fatture in Cloud', $this->getStatusIcon($results['fatture_in_cloud'])];
            }
            
            if (!empty($tableData)) {
                $this->table(['Sistema', 'Stato'], $tableData);
            }
            
            $this->newLine();
            $this->info("⏱️  Tempo totale: {$duration}");
            $this->newLine();

            // Successo se almeno un sistema è stato sincronizzato senza errori critici
            $hasSuccess = collect($results)->filter(fn($r) => $r === true)->isNotEmpty();
            $hasCriticalError = isset($results['database']) && $results['database'] === false;
            
            if ($hasSuccess && !$hasCriticalError) {
                $this->info('Sincronizzazione completata!');
                
                Log::info('[SyncAll] Sincronizzazione completa terminata', [
                    'results' => $results,
                    'duration' => $duration,
                    'forced' => $this->option('force'),
                    'full_sync' => $this->option('full-sync'),
                    'days' => $this->option('days'),
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Sincronizzazione completata con errori critici');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Errore critico durante la sincronizzazione!');
            $this->error($e->getMessage());
            
            Log::error('[SyncAll] Errore sincronizzazione completa: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Verifica se un comando esiste
     */
    private function commandExists(string $command): bool
    {
        try {
            $commands = Artisan::all();
            return isset($commands[$command]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ottieni icona stato
     */
    private function getStatusIcon($status): string
    {
        if ($status === true) {
            return 'Completato';
        } elseif ($status === false) {
            return '❌ Fallito';
        } else {
            return '⊘ Non disponibile';
        }
    }
}
