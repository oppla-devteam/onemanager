<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class OpplaSyncController extends Controller
{
    /**
     * Sincronizza tutto il database Oppla (partners, restaurants, orders, deliveries)
     */
    public function syncDatabase()
    {
        try {
            Log::info('[OpplaSync] Inizio sincronizzazione database Oppla');
            
            // Esegui comando di sincronizzazione database completo
            Artisan::call('sync:db', ['--force' => true]);
            $output = Artisan::output();
            
            Log::info('[OpplaSync] Sincronizzazione database completata', ['output' => $output]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronizzazione database Oppla completata con successo',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Errore sincronizzazione database: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione del database',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronizza tutto: Database + Stripe + Fatture in Cloud
     */
    public function syncAll()
    {
        try {
            Log::info('[OpplaSync] Inizio sincronizzazione completa (Partners + Restaurants)');
            
            $results = [
                'partners' => 0,
                'restaurants' => 0,
                'errors' => []
            ];
            
            // Step 1: Sincronizza Partners
            Log::info('[OpplaSync] Step 1: Sincronizzazione Partners');
            Artisan::call('oppla:sync-partners', ['--force' => true]);
            $partnersOutput = Artisan::output();
            
            // Estrai numero partners dal output
            if (preg_match('/Totale.*?(\d+)/', $partnersOutput, $matches)) {
                $results['partners'] = (int)$matches[1];
            }
            
            // Step 2: Sincronizza Restaurants (che li collega automaticamente ai partners)
            Log::info('[OpplaSync] Step 2: Sincronizzazione Restaurants');
            Artisan::call('oppla:sync-restaurants', ['--force' => true]);
            $restaurantsOutput = Artisan::output();
            
            // Estrai numero restaurants dal output
            if (preg_match('/Totale.*?(\d+)/', $restaurantsOutput, $matches)) {
                $results['restaurants'] = (int)$matches[1];
            }
            
            Log::info('[OpplaSync] Sincronizzazione completa terminata', $results);
            
            return response()->json([
                'success' => true,
                'message' => 'Sincronizzazione completa terminata con successo',
                'partners' => $results['partners'],
                'restaurants' => $results['restaurants'],
                'output' => "Partners: {$partnersOutput}\n\nRestaurants: {$restaurantsOutput}"
            ]);

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Errore sincronizzazione completa: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione completa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DEPRECATO: Usa syncDatabase() invece
     * Mantenuto per retrocompatibilità
     */
    public function syncClients()
    {
        return $this->syncDatabase();
    }

    /**
     * Test connessione al database Oppla PostgreSQL
     */
    public function testConnection()
    {
        try {
            $tableName = env('OPPLA_DB_TABLE', 'partners');
            
            // Test query count
            $count = DB::connection('oppla')
                ->table($tableName)
                ->where('is_active', true)
                ->count();

            return response()->json([
                'success' => true,
                'connected' => true,
                'database' => 'oppla_postgresql',
                'total_clients' => $count,
                'message' => 'Connessione riuscita al database Oppla'
            ]);

        } catch (\Exception $e) {
            Log::error('[OpplaSync] Test connessione fallito: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'connected' => false,
                'error' => $e->getMessage(),
                'message' => 'Impossibile connettersi al database Oppla PostgreSQL'
            ], 500);
        }
    }
}
