<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportPartnersSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'partners:export-seeder {--file=PartnersProductionSeeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Esporta partners e associazioni client-partner in un seeder per la produzione';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileName = $this->option('file');
        $this->info('📦 Esportazione partners e associazioni in seeder...');

        // Recupera partners
        $partners = DB::table('partners')->get();
        $this->info("Trovati {$partners->count()} partners");

        // Recupera associazioni client-partner (dalla tabella pivot se esiste)
        $clientPartners = [];
        if (DB::getSchemaBuilder()->hasTable('client_partner')) {
            $clientPartners = DB::table('client_partner')->get();
            $this->info("Trovate {$clientPartners->count()} associazioni client-partner");
        }

        if ($partners->isEmpty()) {
            $this->warn('⚠️  Nessun partner trovato!');
            return 1;
        }

        // Genera il contenuto del seeder
        $seederContent = $this->generateSeederContent($partners, $clientPartners, $fileName);

        // Salva il seeder
        $seederPath = database_path("seeders/{$fileName}.php");
        File::put($seederPath, $seederContent);

        $this->info("Seeder creato: {$seederPath}");
        $this->info('');
        $this->info('📋 Per usarlo in produzione:');
        $this->info('1. Carica il file sul server in database/seeders/');
        $this->info("2. Esegui: php artisan db:seed --class={$fileName}");

        return 0;
    }

    /**
     * Genera il contenuto del seeder
     */
    private function generateSeederContent($partners, $clientPartners, $className): string
    {
        $date = now()->format('Y-m-d H:i:s');
        
        // Converti in array
        $partnersData = $partners->map(function($p) {
            return (array) $p;
        })->toArray();
        
        $clientPartnersData = collect($clientPartners)->map(function($cp) {
            return (array) $cp;
        })->toArray();

        $partnersPhp = var_export($partnersData, true);
        $clientPartnersPhp = var_export($clientPartnersData, true);
        
        $partnersCount = count($partnersData);
        $clientPartnersCount = count($clientPartnersData);

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder generato automaticamente il {$date}
 * Contiene {$partnersCount} partners e {$clientPartnersCount} associazioni
 */
class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🔄 Importazione partners e associazioni in produzione...');
        
        \$partners = {$partnersPhp};
        
        \$clientPartners = {$clientPartnersPhp};

        DB::beginTransaction();
        
        try {
            // Disabilita foreign key checks temporaneamente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // STEP 1: Svuota tutti i partners esistenti (placeholder)
            \$this->command->warn('🗑️  Eliminazione partners placeholder esistenti...');
            DB::table('partners')->delete();
            
            // STEP 2: Svuota associazioni client-partner se la tabella esiste
            if (DB::getSchemaBuilder()->hasTable('client_partner')) {
                DB::table('client_partner')->delete();
            }

            // Riabilita foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // STEP 3: Importa partners da locale
            \$partnersCreated = 0;

            foreach (\$partners as \$partnerData) {
                // Verifica che il restaurant_id esista, altrimenti impostalo a NULL
                if (isset(\$partnerData['restaurant_id']) && \$partnerData['restaurant_id']) {
                    \$restaurantExists = DB::table('restaurants')->where('id', \$partnerData['restaurant_id'])->exists();
                    if (!\$restaurantExists) {
                        \$partnerData['restaurant_id'] = null;
                    }
                }

                DB::table('partners')->insert(\$partnerData);
                \$partnersCreated++;
            }

            // STEP 4: Importa associazioni client-partner
            \$associationsCreated = 0;
            \$associationsSkipped = 0;

            foreach (\$clientPartners as \$assocData) {
                // Verifica che client e partner esistano
                \$clientExists = DB::table('clients')->where('id', \$assocData['client_id'])->exists();
                \$partnerExists = DB::table('partners')->where('id', \$assocData['partner_id'])->exists();

                if (!\$clientExists || !\$partnerExists) {
                    \$associationsSkipped++;
                    continue;
                }

                DB::table('client_partner')->insert(\$assocData);
                \$associationsCreated++;
            }

            DB::commit();

            \$this->command->info("Importazione completata!");
            \$this->command->info("   Partners:");
            \$this->command->info("     - Creati: {\$partnersCreated}");
            \$this->command->info("   Associazioni:");
            \$this->command->info("     - Create: {\$associationsCreated}");
            \$this->command->info("     - Saltate: {\$associationsSkipped}");

        } catch (\Exception \$e) {
            DB::rollBack();
            \$this->command->error('❌ Errore durante l\'importazione: ' . \$e->getMessage());
            throw \$e;
        }
    }
}

PHP;
    }
}
