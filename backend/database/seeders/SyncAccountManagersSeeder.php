<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SyncAccountManagersSeeder extends Seeder
{
    /**
     * Sincronizza i titolari (account managers) dal database locale a quello di produzione
     * 
     * Questo seeder:
     * 1. Legge tutti i titolari dal database locale (MySQL)
     * 2. Svuota le assegnazioni account_manager_id esistenti
     * 3. Crea/aggiorna i titolari in produzione
     * 4. Riassegna i clienti ai titolari come in locale
     */
    public function run(): void
    {
        $this->command->info('🔄 Sincronizzazione Titolari (Account Managers) - Locale → Produzione');
        $this->command->newLine();
        
        // Determina quale connessione è la locale e quale la produzione
        $localConnection = 'mysql';  // Database locale (development)
        $prodConnection = config('database.default'); // Database corrente (production)
        
        $this->command->info("📊 Database locale: {$localConnection}");
        $this->command->info("📊 Database produzione: {$prodConnection}");
        $this->command->newLine();
        
        try {
            // Step 1: Elimina clienti "Da completare" in produzione
            $this->command->info('📋 Step 1: Eliminazione clienti "Da completare" in produzione...');
            $this->deleteIncompleteClients($prodConnection);
            
            // Step 2: Leggi titolari dal locale
            $this->command->info('📋 Step 2: Lettura titolari dal database locale...');
            $localUsers = $this->getLocalAccountManagers($localConnection);
            $this->command->info("   Trovati {$localUsers->count()} titolari da sincronizzare");
            $this->command->newLine();
            
            if ($localUsers->isEmpty()) {
                $this->command->warn('⚠️  Nessun titolare trovato nel database locale');
                return;
            }
            
            // Step 3: Crea/aggiorna titolari in produzione
            $this->command->info('📋 Step 3: Creazione/aggiornamento titolari in produzione...');
            $userMapping = $this->syncUsers($localConnection, $prodConnection, $localUsers);
            
            // Step 4: Assegna clienti ai titolari
            $this->command->info('📋 Step 4: Assegnazione clienti ai titolari in produzione...');
            $this->syncClientAssignments($localConnection, $prodConnection, $userMapping);
            
            $this->command->newLine();
            $this->command->info('✅ Sincronizzazione completata con successo!');
            
        } catch (\Exception $e) {
            $this->command->error('❌ Errore: ' . $e->getMessage());
            Log::error('Sync Account Managers Seeder Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Elimina tutti i clienti "Da completare" dal database di produzione
     */
    protected function deleteIncompleteClients(string $connection): void
    {
        $incompleteClients = DB::connection($connection)
            ->table('clients')
            ->where('ragione_sociale', 'like', 'Da completare%')
            ->get();
        
        if ($incompleteClients->count() > 0) {
            $this->command->warn("   ⚠️  Trovati {$incompleteClients->count()} clienti da eliminare");
            
            DB::connection($connection)->transaction(function() use ($connection, $incompleteClients) {
                foreach ($incompleteClients as $client) {
                    DB::connection($connection)
                        ->table('clients')
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
     * Legge tutti i titolari (account managers) dal database locale
     */
    protected function getLocalAccountManagers(string $localConnection)
    {
        return DB::connection($localConnection)
            ->table('users')
            ->whereIn('id', function($query) use ($localConnection) {
                $query->select('account_manager_id')
                    ->from('clients')
                    ->whereNotNull('account_manager_id')
                    ->distinct();
            })
            ->get();
    }
    
    /**
     * Sincronizza gli utenti dal locale alla produzione
     * Ritorna una mappa ID locale → ID produzione
     */
    protected function syncUsers(string $localConnection, string $prodConnection, $localUsers): array
    {
        $userMapping = [];
        
        DB::connection($prodConnection)->transaction(function() use ($localConnection, $prodConnection, $localUsers, &$userMapping) {
            foreach ($localUsers as $localUser) {
                // Cerca se esiste già in produzione (per email)
                $prodUser = DB::connection($prodConnection)
                    ->table('users')
                    ->where('email', $localUser->email)
                    ->first();
                
                if ($prodUser) {
                    $this->command->line("   ↻ Aggiornamento: {$localUser->name} ({$localUser->email})");
                    
                    DB::connection($prodConnection)
                        ->table('users')
                        ->where('id', $prodUser->id)
                        ->update([
                            'name' => $localUser->name,
                            'updated_at' => now(),
                        ]);
                    
                    $userMapping[$localUser->id] = $prodUser->id;
                    
                } else {
                    $this->command->line("   + Creazione: {$localUser->name} ({$localUser->email})");
                    
                    $newUserId = DB::connection($prodConnection)
                        ->table('users')
                        ->insertGetId([
                            'name' => $localUser->name,
                            'email' => $localUser->email,
                            'password' => $localUser->password ?? Hash::make('ChangeMe123!'), // Password temporanea
                            'email_verified_at' => $localUser->email_verified_at,
                            'created_at' => $localUser->created_at ?? now(),
                            'updated_at' => now(),
                        ]);
                    
                    $userMapping[$localUser->id] = $newUserId;
                }
            }
        });
        
        $this->command->newLine();
        return $userMapping;
    }
    
    /**
     * Sincronizza le assegnazioni clienti → titolari
     */
    protected function syncClientAssignments(string $localConnection, string $prodConnection, array $userMapping): void
    {
        // Leggi tutte le assegnazioni dal database locale
        $localAssignments = DB::connection($localConnection)
            ->table('clients')
            ->whereNotNull('account_manager_id')
            ->select('id', 'ragione_sociale', 'email', 'piva', 'codice_fiscale', 'account_manager_id')
            ->get();
        
        $this->command->info("   Trovate {$localAssignments->count()} assegnazioni da sincronizzare");
        $this->command->newLine();
        
        $assigned = 0;
        $notFound = 0;
        
        DB::connection($prodConnection)->transaction(function() use ($prodConnection, $localAssignments, $userMapping, &$assigned, &$notFound) {
            foreach ($localAssignments as $assignment) {
                // Trova il cliente corrispondente in produzione
                // Cerca per: 1) Email, 2) P.IVA, 3) Codice Fiscale, 4) Ragione Sociale
                $prodClient = DB::connection($prodConnection)
                    ->table('clients')
                    ->where(function($query) use ($assignment) {
                        if (!empty($assignment->email)) {
                            $query->where('email', $assignment->email);
                        } elseif (!empty($assignment->piva)) {
                            $query->orWhere('piva', $assignment->piva);
                        } elseif (!empty($assignment->codice_fiscale)) {
                            $query->orWhere('codice_fiscale', $assignment->codice_fiscale);
                        } else {
                            $query->orWhere('ragione_sociale', $assignment->ragione_sociale);
                        }
                    })
                    ->first();
                
                if ($prodClient && isset($userMapping[$assignment->account_manager_id])) {
                    $newManagerId = $userMapping[$assignment->account_manager_id];
                    
                    DB::connection($prodConnection)
                        ->table('clients')
                        ->where('id', $prodClient->id)
                        ->update([
                            'account_manager_id' => $newManagerId,
                            'updated_at' => now(),
                        ]);
                    
                    // Trova il nome del manager
                    $managerName = DB::connection($prodConnection)
                        ->table('users')
                        ->where('id', $newManagerId)
                        ->value('name');
                    
                    $this->command->line("   ✓ {$prodClient->ragione_sociale} → {$managerName}");
                    $assigned++;
                    
                } else {
                    $this->command->warn("   ⚠️  Cliente non trovato: {$assignment->ragione_sociale}");
                    $notFound++;
                }
            }
        });
        
        $this->command->newLine();
        $this->command->info('📊 Statistiche:');
        $this->command->info("   - Titolari sincronizzati: " . count($userMapping));
        $this->command->info("   - Clienti assegnati: {$assigned}");
        if ($notFound > 0) {
            $this->command->warn("   - Clienti non trovati: {$notFound}");
        }
    }
}
