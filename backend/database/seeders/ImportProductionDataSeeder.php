<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportProductionDataSeeder extends Seeder
{
    /**
     * Importa dati Partner e Ristoranti da JSON in produzione
     * 
     * ATTENZIONE: Svuota completamente le tabelle prima di importare!
     */
    public function run(): void
    {
        $this->command->info("\n🚀 IMPORT DATI PRODUZIONE - Partner e Ristoranti");
        $this->command->line(str_repeat("=", 60));
        $this->command->newLine();
        
        try {
            // Step 1: Verifica file JSON
            $partnersFile = base_path('partners_data.json');
            $restaurantsFile = base_path('restaurants_data.json');
            
            if (!File::exists($partnersFile)) {
                $this->command->error("❌ File partners_data.json non trovato nella root del backend!");
                return;
            }
            
            if (!File::exists($restaurantsFile)) {
                $this->command->error("❌ File restaurants_data.json non trovato nella root del backend!");
                return;
            }
            
            $this->command->info("✓ File JSON trovati");
            $this->command->newLine();
            
            // Step 2: Leggi JSON
            $partners = json_decode(File::get($partnersFile), true);
            $restaurants = json_decode(File::get($restaurantsFile), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->command->error("❌ Errore decodifica JSON: " . json_last_error_msg());
                return;
            }
            
            $this->command->info("📊 Dati da importare:");
            $this->command->line("   - Partner: " . count($partners));
            $this->command->line("   - Ristoranti: " . count($restaurants));
            $this->command->newLine();
            
            // Step 3: SVUOTA TABELLE
            $this->command->warn("⚠️  Svuotamento tabelle in corso...");
            
            // Disabilita temporaneamente i foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Svuota Partner
            $oldPartnersCount = DB::table('partners')->count();
            DB::table('partners')->truncate();
            $this->command->line("   ✓ Eliminati {$oldPartnersCount} partner esistenti");
            
            // Svuota Ristoranti
            $oldRestaurantsCount = DB::table('restaurants')->count();
            DB::table('restaurants')->truncate();
            $this->command->line("   ✓ Eliminati {$oldRestaurantsCount} ristoranti esistenti");
            
            // Riabilita i foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            $this->command->newLine();
            
            // Step 4: IMPORTA RISTORANTI
            $this->command->info("📥 Importazione Ristoranti...");
            $restaurantMapping = [];
            
            // Preleva tutti gli ID dei clienti esistenti in produzione
            $existingClientIds = DB::table('clients')->pluck('id')->toArray();
            
            foreach ($restaurants as $restaurant) {
                $oldId = $restaurant['id'];
                unset($restaurant['id']); // Auto-increment gestirà il nuovo ID
                
                // Se il client_id non esiste in produzione, lo impostiamo a NULL
                if (!empty($restaurant['client_id']) && !in_array($restaurant['client_id'], $existingClientIds)) {
                    $this->command->warn("   ⚠️  Client ID {$restaurant['client_id']} non trovato per {$restaurant['nome']} - impostato a NULL");
                    $restaurant['client_id'] = null;
                }
                
                $newId = DB::table('restaurants')->insertGetId($restaurant);
                $restaurantMapping[$oldId] = $newId;
                
                $nome = $restaurant['nome'] ?? 'N/A';
                $this->command->line("   + {$nome} (ID: {$oldId} → {$newId})");
            }
            
            $this->command->newLine();
            
            // Step 5: IMPORTA PARTNER
            $this->command->info("📥 Importazione Partner...");
            
            foreach ($partners as $partner) {
                // Mappa il restaurant_id vecchio con quello nuovo
                if (!empty($partner['restaurant_id']) && isset($restaurantMapping[$partner['restaurant_id']])) {
                    $partner['restaurant_id'] = $restaurantMapping[$partner['restaurant_id']];
                }
                
                unset($partner['id']); // Auto-increment gestirà il nuovo ID
                
                DB::table('partners')->insert($partner);
                
                $nome = ($partner['nome'] ?? '') . ' ' . ($partner['cognome'] ?? '');
                $restaurant = $partner['restaurant_id'] ?? 'Nessuno';
                $this->command->line("   + {$nome} → Ristorante: {$restaurant}");
            }
            
            $this->command->newLine();
            $this->command->line(str_repeat("=", 60));
            $this->command->info("✅ IMPORT COMPLETATO CON SUCCESSO!");
            $this->command->newLine();
            
            // Verifica finale
            $finalPartners = DB::table('partners')->count();
            $finalRestaurants = DB::table('restaurants')->count();
            
            $this->command->info("📊 Dati finali in database:");
            $this->command->line("   - Partner: {$finalPartners}");
            $this->command->line("   - Ristoranti: {$finalRestaurants}");
            $this->command->newLine();
            
        } catch (\Exception $e) {
            $this->command->error("\n❌ ERRORE: " . $e->getMessage());
            $this->command->line($e->getTraceAsString());
            throw $e;
        }
    }
}
