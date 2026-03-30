<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportAccountManagersSeeder extends Seeder
{
    /**
     * Importa i titolari (account managers) da file JSON esportato dal database locale
     * 
     * Questo seeder:
     * 1. Legge il file JSON esportato dal locale
     * 2. Elimina clienti "Da completare"
     * 3. Crea/aggiorna i titolari in produzione
     * 4. Riassegna i clienti ai titolari come in locale
     */
    public function run(): void
    {
        $this->command->info('📥 Importazione Titolari (Account Managers) da Export Locale');
        $this->command->newLine();
        
        try {
            // Step 1: Trova il file JSON più recente
            $this->command->info('📋 Step 1: Ricerca file export...');
            $jsonFile = $this->findLatestExportFile();
            
            if (!$jsonFile) {
                $this->command->error('❌ Nessun file export trovato in storage/app/');
                $this->command->info('   Esegui prima in locale: php export_account_managers.php');
                return;
            }
            
            $this->command->info("   File trovato: {$jsonFile}");
            $this->command->newLine();
            
            // Step 2: Leggi i dati dal file JSON
            $this->command->info('📋 Step 2: Lettura dati dal file...');
            $data = json_decode(Storage::get($jsonFile), true);
            
            if (!$data || !isset($data['users']) || !isset($data['assignments'])) {
                $this->command->error('❌ File JSON non valido o corrotto');
                return;
            }
            
            $this->command->info("   Data export: {$data['export_date']}");
            $this->command->info("   Titolari: {$data['stats']['total_users']}");
            $this->command->info("   Assegnazioni: {$data['stats']['total_assignments']}");
            $this->command->newLine();
            
            // Step 3: Elimina clienti "Da completare"
            $this->command->info('📋 Step 3: Eliminazione clienti "Da completare"...');
            $this->deleteIncompleteClients();
            
            // Step 4: Importa titolari
            $this->command->info('📋 Step 4: Importazione titolari...');
            $userMapping = $this->importUsers($data['users']);
            
            // Step 5: Assegna clienti ai titolari
            $this->command->info('📋 Step 5: Assegnazione clienti ai titolari...');
            $this->importAssignments($data['assignments'], $userMapping);
            
            $this->command->newLine();
            $this->command->info('✅ Importazione completata con successo!');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Errore: ' . $e->getMessage());
            Log::error('Import Account Managers Seeder Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Trova il file export JSON più recente
     */
    protected function findLatestExportFile(): ?string
    {
        $files = Storage::files();
        $exportFiles = array_filter($files, function($file) {
            return str_starts_with(basename($file), 'account_managers_export_') && str_ends_with($file, '.json');
        });
        
        if (empty($exportFiles)) {
            return null;
        }
        
        // Ordina per data di modifica e prendi il più recente
        usort($exportFiles, function($a, $b) {
            return Storage::lastModified($b) - Storage::lastModified($a);
        });
        
        return $exportFiles[0];
    }
    
    /**
     * Elimina tutti i clienti "Da completare"
     */
    protected function deleteIncompleteClients(): void
    {
        $incompleteClients = DB::table('clients')
            ->where('ragione_sociale', 'like', 'Da completare%')
            ->get();
        
        if ($incompleteClients->count() > 0) {
            $this->command->warn("   ⚠️  Trovati {$incompleteClients->count()} clienti da eliminare");
            
            DB::transaction(function() use ($incompleteClients) {
                foreach ($incompleteClients as $client) {
                    DB::table('clients')
                        ->where('id', $client->id)
                        ->delete();
                    $this->command->line("   ✓ Eliminato: {$client->ragione_sociale}");
                }
            });
        } else {
            $this->command->info('   ✅ Nessun cliente "Da completare" trovato');
        }
        $this->command->newLine();
    }
    
    /**
     * Importa gli utenti (titolari)
     * Ritorna una mappa: ID locale → ID produzione
     */
    protected function importUsers(array $users): array
    {
        $userMapping = [];
        
        DB::transaction(function() use ($users, &$userMapping) {
            foreach ($users as $userData) {
                // Cerca se esiste già (per email)
                $existingUser = DB::table('users')
                    ->where('email', $userData['email'])
                    ->first();
                
                if ($existingUser) {
                    $this->command->line("   ↻ Aggiornamento: {$userData['name']} ({$userData['email']})");
                    
                    DB::table('users')
                        ->where('id', $existingUser->id)
                        ->update([
                            'name' => $userData['name'],
                            'updated_at' => now(),
                        ]);
                    
                    $userMapping[$userData['id']] = $existingUser->id;
                    
                } else {
                    $this->command->line("   + Creazione: {$userData['name']} ({$userData['email']})");
                    
                    $newUserId = DB::table('users')
                        ->insertGetId([
                            'name' => $userData['name'],
                            'email' => $userData['email'],
                            'password' => $userData['password'], // Mantiene la stessa password
                            'email_verified_at' => $userData['email_verified_at'],
                            'created_at' => $userData['created_at'] ?? now(),
                            'updated_at' => now(),
                        ]);
                    
                    $userMapping[$userData['id']] = $newUserId;
                }
            }
        });
        
        $this->command->newLine();
        return $userMapping;
    }
    
    /**
     * Importa le assegnazioni clienti → titolari
     */
    protected function importAssignments(array $assignments, array $userMapping): void
    {
        $this->command->info("   Trovate {$assignments->count()} assegnazioni da importare");
        $this->command->newLine();
        
        $assigned = 0;
        $notFound = 0;
        
        DB::transaction(function() use ($assignments, $userMapping, &$assigned, &$notFound) {
            foreach ($assignments as $assignment) {
                // Trova il cliente corrispondente in produzione
                // Cerca per: 1) Email, 2) P.IVA, 3) Codice Fiscale, 4) Ragione Sociale
                $prodClient = DB::table('clients')
                    ->where(function($query) use ($assignment) {
                        if (!empty($assignment['email'])) {
                            $query->where('email', $assignment['email']);
                        } elseif (!empty($assignment['piva'])) {
                            $query->orWhere('piva', $assignment['piva']);
                        } elseif (!empty($assignment['codice_fiscale'])) {
                            $query->orWhere('codice_fiscale', $assignment['codice_fiscale']);
                        } else {
                            $query->orWhere('ragione_sociale', $assignment['ragione_sociale']);
                        }
                    })
                    ->first();
                
                if ($prodClient && isset($userMapping[$assignment['account_manager_id']])) {
                    $newManagerId = $userMapping[$assignment['account_manager_id']];
                    
                    DB::table('clients')
                        ->where('id', $prodClient->id)
                        ->update([
                            'account_manager_id' => $newManagerId,
                            'updated_at' => now(),
                        ]);
                    
                    // Trova il nome del manager
                    $managerName = DB::table('users')
                        ->where('id', $newManagerId)
                        ->value('name');
                    
                    $this->command->line("   ✓ {$prodClient->ragione_sociale} → {$managerName}");
                    $assigned++;
                    
                } else {
                    $this->command->warn("   ⚠️  Cliente non trovato: {$assignment['ragione_sociale']}");
                    $notFound++;
                }
            }
        });
        
        $this->command->newLine();
        $this->command->info('📊 Statistiche:');
        $this->command->info("   - Titolari importati: " . count($userMapping));
        $this->command->info("   - Clienti assegnati: {$assigned}");
        if ($notFound > 0) {
            $this->command->warn("   - Clienti non trovati: {$notFound}");
        }
    }
}
