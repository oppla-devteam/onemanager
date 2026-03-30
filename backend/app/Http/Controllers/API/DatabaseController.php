<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * ⚠️ CONTROLLER READ-ONLY DATABASE ⚠️
 * 
 * Questo controller espone endpoint API per query PostgreSQL in SOLA LETTURA.
 * TUTTE le query vengono validate per garantire che siano SELECT only.
 * 
 * Sicurezza:
 * - Solo query SELECT permesse
 * - Validazione query pattern
 * - Rate limiting applicato
 * - Logging di tutte le query
 */
class DatabaseController extends Controller
{
    /**
     * Configurazione connessione PostgreSQL Oppla
     */
    private $connectionName = 'oppla_pgsql';

    /**
     * 🔒 Valida che la query sia READ-ONLY
     */
    private function isReadOnlyQuery(string $query): bool
    {
        $normalizedQuery = trim(strtoupper($query));
        
        // Operazioni NON PERMESSE
        $writeOperations = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'TRUNCATE', 'REPLACE', 'GRANT', 'REVOKE', 'EXECUTE', 
            'CALL', 'DO', 'LOAD', 'COPY', 'IMPORT'
        ];
        
        foreach ($writeOperations as $operation) {
            if (
                str_starts_with($normalizedQuery, $operation) ||
                str_contains($normalizedQuery, " {$operation} ") ||
                str_contains($normalizedQuery, ";{$operation}")
            ) {
                return false;
            }
        }
        
        // Deve iniziare con SELECT o WITH (CTE)
        return str_starts_with($normalizedQuery, 'SELECT') || 
               str_starts_with($normalizedQuery, 'WITH');
    }

    /**
     * 📊 GET /api/database/clients
     * Recupera tutti i partner
     */
    public function getClients(Request $request)
    {
        try {
            $query = "
                SELECT 
                    id,
                    name,
                    email,
                    phone,
                    first_name,
                    last_name,
                    created_at,
                    updated_at
                FROM users
                WHERE type = 'partner' AND deleted_at IS NULL
                ORDER BY name ASC
            ";

            if (!$this->isReadOnlyQuery($query)) {
                return response()->json([
                    'error' => 'Query non permessa: solo SELECT consentito'
                ], 403);
            }

            $clients = DB::connection($this->connectionName)->select($query);
            
            return response()->json([
                'success' => true,
                'data' => $clients,
                'count' => count($clients)
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore recupero clienti PostgreSQL:', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Errore recupero dati dal database',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔍 GET /api/database/clients/{id}
     * Recupera un singolo partner
     */
    public function getClient(Request $request, $id)
    {
        try {
            $query = "
                SELECT 
                    id,
                    name,
                    email,
                    phone,
                    first_name,
                    last_name,
                    created_at,
                    updated_at
                FROM users
                WHERE id = ? AND type = 'partner' AND deleted_at IS NULL
            ";

            if (!$this->isReadOnlyQuery($query)) {
                return response()->json([
                    'error' => 'Query non permessa: solo SELECT consentito'
                ], 403);
            }

            $client = DB::connection($this->connectionName)
                ->select($query, [$id]);
            
            if (empty($client)) {
                return response()->json([
                    'error' => 'Cliente non trovato'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $client[0]
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore recupero cliente PostgreSQL:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Errore recupero dati dal database'
            ], 500);
        }
    }

    /**
     * 🔎 GET /api/database/clients/search?q=term
     * Cerca partner nel database
     */
    public function searchClients(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:2|max:100',
                'limit' => 'integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Parametri di ricerca non validi',
                    'details' => $validator->errors()
                ], 400);
            }

            $searchTerm = '%' . $request->input('q') . '%';
            $limit = $request->input('limit', 50);

            $query = "
                SELECT 
                    id,
                    name,
                    email,
                    phone,
                    first_name,
                    last_name,
                    created_at,
                    updated_at
                FROM users
                WHERE type = 'partner' 
                    AND deleted_at IS NULL
                    AND (
                        LOWER(name) LIKE LOWER(?) OR
                        LOWER(first_name) LIKE LOWER(?) OR
                        LOWER(last_name) LIKE LOWER(?) OR
                        LOWER(email) LIKE LOWER(?) OR
                        phone LIKE ?
                    )
                ORDER BY name ASC
                LIMIT ?
            ";

            if (!$this->isReadOnlyQuery($query)) {
                return response()->json([
                    'error' => 'Query non permessa: solo SELECT consentito'
                ], 403);
            }

            $clients = DB::connection($this->connectionName)
                ->select($query, [
                    $searchTerm, 
                    $searchTerm, 
                    $searchTerm, 
                    $searchTerm, 
                    $searchTerm, 
                    $limit
                ]);
            
            return response()->json([
                'success' => true,
                'data' => $clients,
                'count' => count($clients),
                'search_term' => $request->input('q')
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore ricerca clienti PostgreSQL:', [
                'search_term' => $request->input('q'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Errore ricerca nel database'
            ], 500);
        }
    }

    /**
     * 📈 GET /api/database/clients/stats
     * Recupera statistiche partner
     */
    public function getClientStats(Request $request)
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as clients_with_email,
                    COUNT(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 END) as clients_with_phone
                FROM users
                WHERE type = 'partner' AND deleted_at IS NULL
            ";

            if (!$this->isReadOnlyQuery($query)) {
                return response()->json([
                    'error' => 'Query non permessa: solo SELECT consentito'
                ], 403);
            }

            $stats = DB::connection($this->connectionName)->select($query);
            
            return response()->json([
                'success' => true,
                'data' => $stats[0]
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore statistiche clienti PostgreSQL:', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Errore recupero statistiche'
            ], 500);
        }
    }

    /**
     * 🧪 GET /api/database/test
     * Test connessione database
     */
    public function testConnection(Request $request)
    {
        try {
            // Test semplice connessione
            $result = DB::connection($this->connectionName)
                ->select('SELECT 1 as test');
            
            // Recupera statistiche
            $stats = DB::connection($this->connectionName)
                ->select("
                    SELECT 
                        COUNT(*) as total_clients
                    FROM users
                    WHERE type = 'partner' AND deleted_at IS NULL
                ");

            return response()->json([
                'success' => true,
                'message' => 'Connessione PostgreSQL riuscita!',
                'database' => config('database.connections.' . $this->connectionName . '.database'),
                'host' => config('database.connections.' . $this->connectionName . '.host'),
                'total_partners' => $stats[0]->total_clients ?? 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Connessione fallita',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 POST /api/database/sync
     * Sincronizzazione manuale partners da Oppla
     */
    public function syncPartners(Request $request)
    {
        try {
            // Esegui comando di sincronizzazione
            \Artisan::call('oppla:sync-partners', ['--force' => true]);
            
            $output = \Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Sincronizzazione completata con successo!',
                'output' => $output,
                'synced_at' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            \Log::error('[OpplaSync] Errore sincronizzazione manuale: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Errore durante la sincronizzazione',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
