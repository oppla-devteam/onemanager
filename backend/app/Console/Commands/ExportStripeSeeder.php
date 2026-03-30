<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportStripeSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:export-seeder {--file=StripeProductionSeeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Esporta transazioni Stripe e Application Fees in un seeder per la produzione';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileName = $this->option('file');
        $this->info('📦 Esportazione dati Stripe in seeder...');

        // Recupera transactions
        $transactions = DB::table('stripe_transactions')->get();
        $this->info("Trovate {$transactions->count()} transazioni Stripe");

        // Recupera application fees
        $fees = DB::table('application_fees')->get();
        $this->info("Trovate {$fees->count()} application fees");

        if ($transactions->isEmpty() && $fees->isEmpty()) {
            $this->warn('⚠️  Nessun dato Stripe trovato!');
            return 1;
        }

        // Genera il contenuto del seeder
        $seederContent = $this->generateSeederContent($transactions, $fees, $fileName);

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
    private function generateSeederContent($transactions, $fees, $className): string
    {
        $date = now()->format('Y-m-d H:i:s');
        
        // Converti collections in array e gestisci i campi JSON
        $transactionsData = $transactions->map(function($t) {
            $data = (array) $t;
            // Decodifica il campo metadata se è JSON
            if (isset($data['metadata']) && is_string($data['metadata'])) {
                $data['metadata'] = json_decode($data['metadata'], true);
            }
            return $data;
        })->toArray();
        
        $feesData = $fees->map(function($f) {
            $data = (array) $f;
            // Decodifica il campo raw_data se è JSON
            if (isset($data['raw_data']) && is_string($data['raw_data'])) {
                $data['raw_data'] = json_decode($data['raw_data'], true);
            }
            return $data;
        })->toArray();

        $transactionsPhp = var_export($transactionsData, true);
        $feesPhp = var_export($feesData, true);

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder generato automaticamente il {$date}
 * Contiene {$transactions->count()} transazioni Stripe e {$fees->count()} application fees
 */
class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🔄 Importazione dati Stripe in produzione...');
        
        \$transactions = {$transactionsPhp};
        
        \$fees = {$feesPhp};

        DB::beginTransaction();
        
        try {
            // Importa transazioni Stripe
            \$transCreated = 0;
            \$transUpdated = 0;

            foreach (\$transactions as \$transData) {
                // Converti metadata in JSON se è array
                if (isset(\$transData['metadata']) && is_array(\$transData['metadata'])) {
                    \$transData['metadata'] = json_encode(\$transData['metadata']);
                }

                \$existing = DB::table('stripe_transactions')
                    ->where('transaction_id', \$transData['transaction_id'])
                    ->first();

                if (\$existing) {
                    DB::table('stripe_transactions')
                        ->where('transaction_id', \$transData['transaction_id'])
                        ->update(\$transData);
                    \$transUpdated++;
                } else {
                    DB::table('stripe_transactions')->insert(\$transData);
                    \$transCreated++;
                }
            }

            // Importa application fees
            \$feesCreated = 0;
            \$feesUpdated = 0;
            \$feesSkipped = 0;

            foreach (\$fees as \$feeData) {
                // Converti raw_data in JSON se è array
                if (isset(\$feeData['raw_data']) && is_array(\$feeData['raw_data'])) {
                    \$feeData['raw_data'] = json_encode(\$feeData['raw_data']);
                }

                // Risolvi client_id: cerca cliente per email o imposta NULL
                if (!empty(\$feeData['partner_email'])) {
                    \$client = DB::table('clients')->where('email', \$feeData['partner_email'])->first();
                    \$feeData['client_id'] = \$client ? \$client->id : null;
                } else {
                    \$feeData['client_id'] = null;
                }

                \$existing = DB::table('application_fees')
                    ->where('stripe_fee_id', \$feeData['stripe_fee_id'])
                    ->first();

                if (\$existing) {
                    DB::table('application_fees')
                        ->where('stripe_fee_id', \$feeData['stripe_fee_id'])
                        ->update(\$feeData);
                    \$feesUpdated++;
                } else {
                    DB::table('application_fees')->insert(\$feeData);
                    \$feesCreated++;
                }
            }

            DB::commit();

            \$this->command->info("Importazione completata!");
            \$this->command->info("   Transazioni Stripe:");
            \$this->command->info("     - Create: {\$transCreated}");
            \$this->command->info("     - Aggiornate: {\$transUpdated}");
            \$this->command->info("   Application Fees:");
            \$this->command->info("     - Create: {\$feesCreated}");
            \$this->command->info("     - Aggiornate: {\$feesUpdated}");
            \$this->command->info("     - Senza cliente: {\$feesSkipped}");

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
