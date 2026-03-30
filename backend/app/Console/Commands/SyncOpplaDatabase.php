<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncOpplaDatabase extends Command
{
    /**
     * Nome e firma del comando
     */
    protected $signature = 'sync:db {--force : Forza sincronizzazione anche se recente}';

    /**
     * Descrizione del comando
     */
    protected $description = 'Sincronizza tutto il database Oppla (partners, restaurants, orders, deliveries)';

    /**
     * Esegui il comando
     */
    public function handle()
    {
        $this->info('🚀 Inizio sincronizzazione completa database Oppla...');
        $this->newLine();
        
        $startTime = now();
        $success = true;

        try {
            // 1. Sincronizza Partners (users type='partner')
            $this->info('1️⃣  Sincronizzazione Partners...');
            $exitCode = Artisan::call('oppla:sync-partners', [
                '--force' => $this->option('force')
            ]);
            
            if ($exitCode === Command::SUCCESS) {
                $this->info('Partners sincronizzati');
                $this->line(Artisan::output());
            } else {
                $this->error('❌ Errore sincronizzazione partners');
                $success = false;
            }
            $this->newLine();

            // 2. Sincronizza Restaurants
            $this->info('2️⃣  Sincronizzazione Restaurants...');
            $exitCode = Artisan::call('oppla:sync-restaurants', [
                '--force' => $this->option('force')
            ]);
            
            if ($exitCode === Command::SUCCESS) {
                $this->info('Restaurants sincronizzati');
                $this->line(Artisan::output());
            } else {
                $this->error('❌ Errore sincronizzazione restaurants');
                $success = false;
            }
            $this->newLine();

            // 3. Sincronizza Orders (se comando esiste)
            if ($this->commandExists('oppla:sync-orders')) {
                $this->info('3️⃣  Sincronizzazione Orders...');
                $exitCode = Artisan::call('oppla:sync-orders', [
                    '--force' => $this->option('force')
                ]);
                
                if ($exitCode === Command::SUCCESS) {
                    $this->info('Orders sincronizzati');
                    $this->line(Artisan::output());
                } else {
                    $this->warn('⚠️  Errore sincronizzazione orders (non bloccante)');
                }
                $this->newLine();
            }

            // 4. Sincronizza Deliveries (se comando esiste)
            if ($this->commandExists('oppla:sync-deliveries')) {
                $this->info('4️⃣  Sincronizzazione Deliveries...');
                $exitCode = Artisan::call('oppla:sync-deliveries', [
                    '--force' => $this->option('force')
                ]);
                
                if ($exitCode === Command::SUCCESS) {
                    $this->info('Deliveries sincronizzate');
                    $this->line(Artisan::output());
                } else {
                    $this->warn('⚠️  Errore sincronizzazione deliveries (non bloccante)');
                }
                $this->newLine();
            }

            $duration = $startTime->diffForHumans(now(), true);

            if ($success) {
                $this->info('Sincronizzazione database Oppla completata con successo!');
                $this->info("⏱️  Tempo impiegato: {$duration}");
                
                Log::info('[SyncDB] Sincronizzazione database completata', [
                    'duration' => $duration,
                    'forced' => $this->option('force'),
                ]);

                return Command::SUCCESS;
            } else {
                $this->error('❌ Sincronizzazione completata con errori');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Errore critico durante la sincronizzazione!');
            $this->error($e->getMessage());
            
            Log::error('[SyncDB] Errore sincronizzazione database: ' . $e->getMessage(), [
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
}
