<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Partner;

class SyncOpplaPartners extends Command
{
    /**
     * Nome e firma del comando
     */
    protected $signature = 'oppla:sync-partners {--force : Forza sincronizzazione anche se recente}';

    /**
     * Descrizione del comando
     */
    protected $description = 'Sincronizza referenti partner (users) dal database PostgreSQL Oppla alla tabella partners locale';

    /**
     * Esegui il comando
     */
    public function handle()
    {
        $this->info('🔄 Inizio sincronizzazione referenti partner da Oppla...');
        
        try {
            // Test connessione
            $this->info('📡 Test connessione database Oppla...');
            DB::connection('oppla_pgsql')->getPdo();
            $this->info('Connessione riuscita!');

            // Recupera users partner da Oppla
            $this->info('📥 Recupero referenti partner da database Oppla...');
            $opplaPartners = DB::connection('oppla_pgsql')
                ->table('users')
                ->select([
                    'id',
                    'email',
                    'phone',
                    'first_name',
                    'last_name',
                    'created_at',
                    'updated_at',
                ])
                ->where('type', 'partner')
                ->whereNull('deleted_at')
                ->get();

            $this->info("Trovati {$opplaPartners->count()} referenti partner su Oppla");

            // Statistiche sincronizzazione
            $created = 0;
            $updated = 0;
            $skipped = 0;

            $this->info('💾 Sincronizzazione con database locale...');
            $progressBar = $this->output->createProgressBar($opplaPartners->count());

            foreach ($opplaPartners as $opplaPartner) {
                // Cerca partner nel database locale per oppla_external_id
                $localPartner = Partner::where('oppla_external_id', $opplaPartner->id)->first();

                $partnerData = [
                    'nome' => $opplaPartner->first_name ?? '',
                    'cognome' => $opplaPartner->last_name ?? '',
                    'email' => $opplaPartner->email,
                    'telefono' => $opplaPartner->phone,
                    'oppla_external_id' => $opplaPartner->id,
                    'oppla_sync_at' => now(),
                    'is_active' => true,
                ];

                if ($localPartner) {
                    // Aggiorna solo se i dati sono cambiati
                    if (
                        $localPartner->nome !== $partnerData['nome'] ||
                        $localPartner->cognome !== $partnerData['cognome'] ||
                        $localPartner->email !== $partnerData['email'] ||
                        $localPartner->telefono !== $partnerData['telefono']
                    ) {
                        $localPartner->update($partnerData);
                        $updated++;
                    } else {
                        // Aggiorna solo la data di sync
                        $localPartner->update(['oppla_sync_at' => now()]);
                        $skipped++;
                    }
                } else {
                    // Crea nuovo partner (senza restaurant_id, verrà collegato dopo)
                    Partner::create($partnerData);
                    $created++;
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
                    ['Referenti creati', $created],
                    ['Referenti aggiornati', $updated],
                    ['Referenti non modificati', $skipped],
                    ['Totale', $opplaPartners->count()],
                ]
            );

            // Log
            Log::info('[OpplaSync] Sincronizzazione partners completata', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => $opplaPartners->count(),
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Errore durante la sincronizzazione!');
            $this->error($e->getMessage());
            
            Log::error('[OpplaSync] Errore sincronizzazione partners: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
