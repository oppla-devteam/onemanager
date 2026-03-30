<?php

namespace App\Console\Commands;

use App\Models\FattureInCloudConnection;
use App\Models\Partner;
use App\Models\Restaurant;
use App\Services\FattureInCloudService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFattureInCloud extends Command
{
    /**
     * Nome e firma del comando
     */
    protected $signature = 'sync:fatture-in-cloud {--force : Forza sincronizzazione anche se recente}';

    /**
     * Descrizione del comando
     */
    protected $description = 'Sincronizza clienti Oppla → Fatture in Cloud';

    private FattureInCloudService $ficService;

    public function __construct(FattureInCloudService $ficService)
    {
        parent::__construct();
        $this->ficService = $ficService;
    }

    /**
     * Esegui il comando
     */
    public function handle()
    {
        $this->info('📄 Inizio sincronizzazione Fatture in Cloud...');
        $this->newLine();

        $startTime = now();

        try {
            // 1. Verifica connessione attiva
            $connection = $this->getActiveConnection();
            
            if (!$connection) {
                $this->error('❌ Nessuna connessione attiva a Fatture in Cloud');
                $this->line('   Esegui prima l\'autenticazione OAuth tramite /api/fatture-in-cloud/authorize');
                return Command::FAILURE;
            }

            $this->info("Connessione attiva: {$connection->company_name}");
            $this->newLine();

            // Verifica se è necessaria una nuova sincronizzazione
            if (!$this->option('force') && $connection->last_sync_at) {
                $minutesSinceSync = now()->diffInMinutes($connection->last_sync_at);
                
                if ($minutesSinceSync < 60) {
                    $this->warn("⚠️  Ultima sincronizzazione: {$minutesSinceSync} minuti fa");
                    $this->line('   Usa --force per forzare la sincronizzazione');
                    return Command::SUCCESS;
                }
            }

            $stats = [
                'partners_synced' => 0,
                'restaurants_synced' => 0,
                'clients_created' => 0,
                'clients_updated' => 0,
                'errors' => 0,
            ];

            // 2. Sincronizza Partners come clienti
            $this->info('1️⃣  Sincronizzazione Partners → Clienti FIC...');
            $this->syncPartners($connection, $stats);
            $this->newLine();

            // 3. Sincronizza Restaurants come clienti
            $this->info('2️⃣  Sincronizzazione Restaurants → Clienti FIC...');
            $this->syncRestaurants($connection, $stats);
            $this->newLine();

            // 4. Aggiorna statistiche connessione
            $connection->updateSyncStats([
                'last_full_sync' => now()->toIso8601String(),
                'stats' => $stats,
            ]);

            $duration = now()->diffInSeconds($startTime);

            // Riepilogo finale
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('SINCRONIZZAZIONE COMPLETATA');
            $this->info('═══════════════════════════════════════════════════════');
            $this->line("⏱️  Durata: {$duration} secondi");
            $this->line("👥 Partners sincronizzati: {$stats['partners_synced']}");
            $this->line("🏪 Restaurants sincronizzati: {$stats['restaurants_synced']}");
            $this->line("➕ Clienti creati: {$stats['clients_created']}");
            $this->line("🔄 Clienti aggiornati: {$stats['clients_updated']}");
            
            if ($stats['errors'] > 0) {
                $this->warn("⚠️  Errori: {$stats['errors']}");
            }

            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('[FIC Sync] Errore sincronizzazione: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('❌ Errore durante la sincronizzazione');
            $this->error('   ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Sincronizza Partners come clienti in Fatture in Cloud
     */
    private function syncPartners(FattureInCloudConnection $connection, array &$stats): void
    {
        $partners = Partner::where('is_active', true)
            ->whereNotNull('email')
            ->get();

        $this->line("   Trovati {$partners->count()} partners attivi");

        $progressBar = $this->output->createProgressBar($partners->count());
        $progressBar->start();

        foreach ($partners as $partner) {
            try {
                $clientData = $this->mapPartnerToFicClient($partner);
                
                // Verifica se il cliente esiste già
                $existingClients = $this->ficService->getClients($connection, [
                    'q' => $partner->email,
                ]);

                if (!empty($existingClients)) {
                    // Cliente già presente, skip o aggiorna
                    $stats['clients_updated']++;
                } else {
                    // Crea nuovo cliente
                    $result = $this->ficService->createClient($connection, $clientData);
                    
                    if ($result) {
                        $stats['clients_created']++;
                    } else {
                        $stats['errors']++;
                    }
                }

                $stats['partners_synced']++;
                $progressBar->advance();

            } catch (\Exception $e) {
                Log::error('[FIC Sync] Errore sincronizzazione partner', [
                    'partner_id' => $partner->id,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Sincronizza Restaurants come clienti in Fatture in Cloud
     */
    private function syncRestaurants(FattureInCloudConnection $connection, array &$stats): void
    {
        $restaurants = Restaurant::where('is_active', true)
            ->whereNotNull('email')
            ->get();

        $this->line("   Trovati {$restaurants->count()} restaurants attivi");

        $progressBar = $this->output->createProgressBar($restaurants->count());
        $progressBar->start();

        foreach ($restaurants as $restaurant) {
            try {
                $clientData = $this->mapRestaurantToFicClient($restaurant);
                
                // Verifica se il cliente esiste già
                $existingClients = $this->ficService->getClients($connection, [
                    'q' => $restaurant->email,
                ]);

                if (!empty($existingClients)) {
                    // Cliente già presente
                    $stats['clients_updated']++;
                } else {
                    // Crea nuovo cliente
                    $result = $this->ficService->createClient($connection, $clientData);
                    
                    if ($result) {
                        $stats['clients_created']++;
                    } else {
                        $stats['errors']++;
                    }
                }

                $stats['restaurants_synced']++;
                $progressBar->advance();

            } catch (\Exception $e) {
                Log::error('[FIC Sync] Errore sincronizzazione restaurant', [
                    'restaurant_id' => $restaurant->id,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Mappa Partner a Cliente FIC
     */
    private function mapPartnerToFicClient(Partner $partner): array
    {
        return [
            'name' => trim("{$partner->nome} {$partner->cognome}"),
            'email' => $partner->email,
            'phone' => $partner->telefono,
            'type' => 'person',
            'code' => "PARTNER-{$partner->id}",
        ];
    }

    /**
     * Mappa Restaurant a Cliente FIC
     */
    private function mapRestaurantToFicClient(Restaurant $restaurant): array
    {
        $data = [
            'name' => $restaurant->nome,
            'email' => $restaurant->email,
            'phone' => $restaurant->telefono,
            'type' => 'company',
            'code' => "REST-{$restaurant->id}",
        ];

        // Aggiungi dati fiscali se disponibili
        if ($restaurant->piva) {
            $data['vat_number'] = $restaurant->piva;
        }

        if ($restaurant->codice_fiscale) {
            $data['tax_code'] = $restaurant->codice_fiscale;
        }

        // Aggiungi indirizzo se disponibile
        if ($restaurant->indirizzo) {
            $data['address_street'] = $restaurant->indirizzo;
            $data['address_city'] = $restaurant->citta;
            $data['address_province'] = $restaurant->provincia;
            $data['address_postal_code'] = $restaurant->cap;
            $data['country'] = 'Italia';
        }

        return $data;
    }

    /**
     * Ottieni connessione attiva
     */
    private function getActiveConnection(): ?FattureInCloudConnection
    {
        return FattureInCloudConnection::where('is_active', true)
            ->orderBy('last_sync_at', 'desc')
            ->first();
    }
}
