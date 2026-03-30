<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Restaurant;
use App\Models\Partner;

class SyncOpplaRestaurants extends Command
{
    /**
     * Nome e firma del comando
     */
    protected $signature = 'oppla:sync-restaurants {--force : Forza sincronizzazione anche se recente}';

    /**
     * Descrizione del comando
     */
    protected $description = 'Sincronizza ristoranti dal database PostgreSQL Oppla alla tabella restaurants locale';

    /**
     * Esegui il comando
     */
    public function handle()
    {
        $this->info('🔄 Inizio sincronizzazione ristoranti da Oppla...');
        
        try {
            // Test connessione
            $this->info('📡 Test connessione database Oppla...');
            DB::connection('oppla_pgsql')->getPdo();
            $this->info('Connessione riuscita!');

            // Recupera ristoranti da Oppla
            $this->info('📥 Recupero ristoranti da database Oppla...');
            $opplaRestaurants = DB::connection('oppla_pgsql')
                ->table('restaurants')
                ->select([
                    'id',
                    'owner_id',
                    'name',
                    'slug',
                    'address',
                    'phone',
                    'description',
                    'image_key',
                    'city_area_id',
                    'created_at',
                    'updated_at',
                ])
                ->get();

            $this->info("Trovati {$opplaRestaurants->count()} ristoranti su Oppla");

            // Statistiche sincronizzazione
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $partnerNotFound = 0;

            $this->info('💾 Sincronizzazione con database locale...');
            $progressBar = $this->output->createProgressBar($opplaRestaurants->count());

            foreach ($opplaRestaurants as $opplaRestaurant) {
                // Cerca il partner locale corrispondente
                $localPartner = Partner::where('oppla_external_id', $opplaRestaurant->owner_id)->first();
                
                if (!$localPartner) {
                    $partnerNotFound++;
                    $progressBar->advance();
                    continue;
                }

                // Cerca restaurant nel database locale per oppla_external_id
                $localRestaurant = Restaurant::where('oppla_external_id', $opplaRestaurant->id)->first();

                $restaurantData = [
                    'oppla_external_id' => $opplaRestaurant->id,
                    'nome' => $opplaRestaurant->name,
                    'indirizzo' => $opplaRestaurant->address,
                    'telefono' => $opplaRestaurant->phone,
                    'description' => $opplaRestaurant->description,
                    'oppla_sync_at' => now(),
                    'is_active' => true,
                    // client_id verrà assegnato manualmente dall'admin
                ];

                if ($localRestaurant) {
                    // Aggiorna dati
                    $localRestaurant->update($restaurantData);
                    $updated++;
                    
                    // Aggiorna collegamento partner se cambiato
                    if ($localPartner->restaurant_id !== $localRestaurant->id) {
                        $localPartner->update(['restaurant_id' => $localRestaurant->id]);
                    }
                } else {
                    // Crea nuovo ristorante
                    $newRestaurant = Restaurant::create($restaurantData);
                    $created++;
                    
                    // Collega il partner al ristorante
                    $localPartner->update(['restaurant_id' => $newRestaurant->id]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Riepilogo
            $this->info('Sincronizzazione completata!');
            $this->table(
                ['Operazione', 'Quantità'],
                [
                    ['Ristoranti creati', $created],
                    ['Ristoranti aggiornati', $updated],
                    ['Ristoranti skippati (partner non trovato)', $partnerNotFound],
                    ['Totale', $opplaRestaurants->count()],
                ]
            );

            if ($partnerNotFound > 0) {
                $this->warn("⚠️  {$partnerNotFound} ristoranti non sincronizzati perché il partner non è stato trovato.");
                $this->warn("   Esegui prima: php artisan oppla:sync-partners");
            }

            // Log
            Log::info('[OpplaSync] Sincronizzazione restaurants completata', [
                'created' => $created,
                'updated' => $updated,
                'partner_not_found' => $partnerNotFound,
                'total' => $opplaRestaurants->count(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Errore durante la sincronizzazione!');
            $this->error($e->getMessage());
            
            Log::error('[OpplaSync] Errore sincronizzazione restaurants: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
