<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use Illuminate\Support\Facades\File;

class ExportClientsSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:export-seeder {--file=ClientsProductionSeeder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Esporta tutti i clienti in un seeder per la produzione';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fileName = $this->option('file');
        $this->info('📦 Esportazione clienti con titolari in seeder...');

        // Recupera tutti i clienti
        $clients = Client::all();
        $this->info("Trovati {$clients->count()} clienti");

        if ($clients->isEmpty()) {
            $this->warn('⚠️  Nessun cliente trovato!');
            return 1;
        }

        // Statistiche
        $withPartners = $clients->filter(fn($c) => $c->oppla_user_id || ($c->oppla_restaurant_ids && count($c->oppla_restaurant_ids) > 0))->count();
        $this->info("   - Con titolari/partner: {$withPartners}");
        $this->info("   - Senza titolari: " . ($clients->count() - $withPartners));

        // Genera il contenuto del seeder
        $seederContent = $this->generateSeederContent($clients, $fileName);

        // Salva il seeder
        $seederPath = database_path("seeders/{$fileName}.php");
        File::put($seederPath, $seederContent);

        $this->info("Seeder creato: {$seederPath}");
        $this->info('');
        $this->info('📋 Per usarlo in produzione:');
        $this->info('1. Carica il file sul server in database/seeders/');
        $this->info("2. Esegui: php artisan db:seed --class={$fileName}");
        $this->info('');
        $this->info("💡 Oppure usa il comando: php artisan clients:import-from-local");

        return 0;
    }

    /**
     * Genera il contenuto del seeder
     */
    private function generateSeederContent($clients, $className): string
    {
        $date = now()->format('Y-m-d H:i:s');
        $clientsData = [];

        foreach ($clients as $client) {
            $clientsData[] = $this->formatClientData($client);
        }

        $clientsPhp = var_export($clientsData, true);

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

/**
 * Seeder generato automaticamente il {$date}
 * Contiene {$clients->count()} clienti con titolari/partner esportati dal database locale
 */
class {$className} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \$this->command->info('🔄 Importazione clienti in produzione...');
        
        \$clients = {$clientsPhp};

        DB::beginTransaction();
        
        try {
            \$created = 0;
            \$updated = 0;
            \$skipped = 0;

            foreach (\$clients as \$clientData) {
                // Controlla se il cliente esiste già (per email o P.IVA)
                \$existing = Client::where('email', \$clientData['email'])
                    ->orWhere(function(\$query) use (\$clientData) {
                        if (!empty(\$clientData['piva'])) {
                            \$query->where('piva', \$clientData['piva']);
                        }
                    })
                    ->first();

                if (\$existing) {
                    // Aggiorna solo se i dati sono diversi
                    \$existing->update(\$clientData);
                    \$updated++;
                } else {
                    // Crea nuovo cliente
                    Client::create(\$clientData);
                    \$created++;
                }
            }

            DB::commit();

            \$this->command->info("Importazione completata!");
            \$this->command->info("   - Creati: {\$created}");

        } catch (\Exception \$e) {
            DB::rollBack();
            \$this->command->error('❌ Errore durante l\'importazione: ' . \$e->getMessage());
            throw \$e;
        }
    }
}

PHP;
    }

    /**
     * Formatta i dati del cliente per l'export (con tutti i campi disponibili)
     */
    private function formatClientData(Client $client): array
    {
        return [
            'ragione_sociale' => $client->ragione_sociale,
            'type' => $client->type ?? 'partner_oppla',
            'email' => $client->email,
            'piva' => $client->piva,
            'codice_fiscale' => $client->codice_fiscale,
            'codice_destinatario' => $client->codice_destinatario,
            'phone' => $client->phone,
            'telefono' => $client->telefono ?? $client->phone,
            'pec' => $client->pec,
            'iban' => $client->iban,
            'indirizzo' => $client->indirizzo,
            'citta' => $client->citta,
            'provincia' => $client->provincia,
            'cap' => $client->cap,
            'is_active' => $client->is_active ?? true,
            'source' => $client->source ?? 'imported',
            'stripe_customer_id' => $client->stripe_customer_id,
            'fatture_in_cloud_id' => $client->fatture_in_cloud_id,
            'oppla_user_id' => $client->oppla_user_id,
            'oppla_restaurant_ids' => is_array($client->oppla_restaurant_ids) ? json_encode($client->oppla_restaurant_ids) : null,
            'notes' => $client->notes,
            'created_at' => $client->created_at ? $client->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $client->updated_at ? $client->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
