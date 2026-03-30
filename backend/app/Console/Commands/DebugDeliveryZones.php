<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugDeliveryZones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:delivery-zones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug delivery zones from OPPLA database to check if restaurant names are being imported instead of zone names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Interrogazione database OPPLA...');
        $this->newLine();

        try {
            // Query le city_areas dal database OPPLA
            $cityAreas = DB::connection('oppla')
                ->table('city_areas as ca')
                ->join('cities as c', 'ca.city_id', '=', 'c.id')
                ->leftJoin('logistic_partners as lp', 'ca.logistic_partner_id', '=', 'lp.id')
                ->select(
                    'ca.id as oppla_id',
                    'ca.name as area_name',
                    'ca.slug',
                    'c.name as city_name',
                    'lp.name as logistic_partner'
                )
                ->orderBy('c.name')
                ->orderBy('ca.name')
                ->limit(50)
                ->get();

            $this->info("✅ Trovate {$cityAreas->count()} city_areas nel database OPPLA");
            $this->newLine();

            // Mostra i risultati in una tabella
            $tableData = $cityAreas->map(function($area) {
                return [
                    'ID' => $area->oppla_id,
                    'Nome Area' => $area->area_name,
                    'Città' => $area->city_name,
                    'Partner Logistico' => $area->logistic_partner ?? 'N/A',
                    'Slug' => $area->slug,
                ];
            })->toArray();

            $this->table(
                ['ID', 'Nome Area', 'Città', 'Partner Logistico', 'Slug'],
                $tableData
            );

            $this->newLine();

            // Verifica se ci sono nomi che sembrano ristoranti
            $this->info('🔍 Analisi nomi...');
            $this->newLine();

            $suspiciousNames = $cityAreas->filter(function($area) {
                $name = strtolower($area->area_name);
                // Keywords che indicano un ristorante invece di una zona geografica
                $restaurantKeywords = ['pizzeria', 'ristorante', 'trattoria', 'bar', 'caffè', 'osteria', 'taverna'];

                foreach ($restaurantKeywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        return true;
                    }
                }
                return false;
            });

            if ($suspiciousNames->count() > 0) {
                $this->warn("⚠️  Trovati {$suspiciousNames->count()} nomi che sembrano ristoranti invece di zone geografiche:");
                $this->newLine();

                foreach ($suspiciousNames as $area) {
                    $this->line("  • {$area->area_name} ({$area->city_name}) - ID: {$area->oppla_id}");
                }

                $this->newLine();
                $this->error('❌ PROBLEMA CONFERMATO: Alcune city_areas hanno nomi di ristoranti invece di zone geografiche!');
                $this->newLine();
                $this->info('Soluzione: Correggere i dati nel database OPPLA o filtrare questi nomi durante la sincronizzazione.');
            } else {
                $this->info('✅ Nessun nome sospetto trovato. Le city_areas sembrano essere zone geografiche corrette.');
            }

            $this->newLine();

            // Mostra le zone locali
            $localZones = DB::table('delivery_zones')
                ->where('is_active', true)
                ->orderBy('city')
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'city', 'source', 'oppla_id']);

            $this->info("📦 Zone di consegna nel database locale: {$localZones->count()}");
            $this->newLine();

            if ($localZones->count() > 0) {
                $localTableData = $localZones->map(function($zone) {
                    return [
                        'ID Locale' => $zone->id,
                        'Nome' => $zone->name,
                        'Città' => $zone->city,
                        'Origine' => $zone->source,
                        'ID OPPLA' => $zone->oppla_id ?? 'N/A',
                    ];
                })->toArray();

                $this->table(
                    ['ID Locale', 'Nome', 'Città', 'Origine', 'ID OPPLA'],
                    $localTableData
                );
            }

        } catch (\Exception $e) {
            $this->error('❌ Errore durante l\'interrogazione del database:');
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('Verifica che:');
            $this->line('1. Il database OPPLA sia configurato correttamente nel file .env');
            $this->line('2. Le credenziali siano corrette (OPPLA_DB_HOST, OPPLA_DB_NAME, OPPLA_DB_USER, OPPLA_DB_PASSWORD)');
            $this->line('3. La connessione al database sia attiva');
            return 1;
        }

        return 0;
    }
}
