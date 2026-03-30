<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryZone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SyncDeliveryZones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oppla:sync-zones {--dry-run : Preview without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizza le zone geografiche (city_areas) da Oppla database';

    /**
     * Restaurant keywords for filtering
     */
    private array $restaurantKeywords = [
        'pizzeria', 'ristorante', 'trattoria', 'osteria', 'bar',
        'cafè', 'cafe', 'bistrot', 'bistro', 'pub', 'paninoteca',
        'hamburgeria', 'gelateria', 'pasticceria', 'braceria',
        'steakhouse', 'sushi', 'poke', 'kebab', 'street food',
        'da ', 'al ', 'la ', 'il ', 'lo ', "l'",
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Sincronizzazione zone geografiche da Oppla database...');
        if ($dryRun) {
            $this->warn('🔸 DRY RUN MODE - Nessuna modifica al database');
        }
        $this->newLine();

        set_time_limit(300);

        try {
            Log::info('[SyncZones] Inizio sincronizzazione city_areas da OPPLA DB');

            // Query city_areas with city names
            $opplaZones = DB::connection('oppla_readonly')
                ->table('city_areas')
                ->join('cities', 'city_areas.city_id', '=', 'cities.id')
                ->select(
                    'city_areas.id as oppla_id',
                    'city_areas.name as zone_name',
                    'cities.name as city_name',
                    'city_areas.default_delivery_area as geometry'
                )
                ->get();

            $this->info("📦 Trovate {$opplaZones->count()} zone nel database OPPLA");

            $syncedCount = 0;
            $filteredCount = 0;
            $bar = $this->output->createProgressBar($opplaZones->count());
            $bar->start();

            foreach ($opplaZones as $zone) {
                try {
                    // Apply restaurant name filtering
                    if ($this->isRestaurantName($zone->zone_name)) {
                        $filteredCount++;
                        Log::debug('[SyncZones] Filtered restaurant name', [
                            'name' => $zone->zone_name
                        ]);
                        $bar->advance();
                        continue;
                    }

                    if (!$dryRun) {
                        // Create or update zone
                        DeliveryZone::updateOrCreate(
                            ['oppla_id' => $zone->oppla_id],
                            [
                                'name' => $zone->zone_name,
                                'city' => $zone->city_name,
                                'description' => 'Zona sincronizzata da OPPLA',
                                'postal_codes' => [],
                                'price_ranges' => [],
                                'geometry' => $zone->geometry ? json_decode($zone->geometry, true) : null,
                                'source' => 'oppla_sync',
                                'is_active' => true,
                            ]
                        );
                    }

                    $syncedCount++;

                } catch (\Exception $e) {
                    Log::warning('[SyncZones] Errore sync zona', [
                        'zone' => $zone->zone_name,
                        'error' => $e->getMessage()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            Log::info('[SyncZones] Sincronizzazione completata', [
                'synced' => $syncedCount,
                'filtered' => $filteredCount
            ]);

            $this->info("✅ Sincronizzazione completata:");
            $this->info("   - {$syncedCount} zone geografiche sincronizzate");
            $this->info("   - {$filteredCount} nomi di ristoranti filtrati");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Errore durante la sincronizzazione:');
            $this->error($e->getMessage());
            Log::error('[SyncZones] Errore: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Check if a name is a restaurant name
     */
    private function isRestaurantName(string $name): bool
    {
        $nameLower = mb_strtolower($name);

        // Check for restaurant keywords
        foreach ($this->restaurantKeywords as $keyword) {
            if (str_contains($nameLower, $keyword)) {
                return true;
            }
        }

        // Additional patterns
        if (preg_match("/[ld]'[a-z]/i", $name)) {
            return true; // Contains L' or D'
        }

        if (str_contains($name, '&')) {
            return true; // Contains & symbol
        }

        if (preg_match('/^\d+\s+[a-z]/i', $name)) {
            return true; // Starts with number + word
        }

        if (str_word_count($name) > 3) {
            $hasGeoIndicator = preg_match('/(centro|nord|sud|est|ovest|zona|area)/i', $name);
            if (!$hasGeoIndicator) {
                return true; // Long name without geographic indicators
            }
        }

        return false;
    }
}
