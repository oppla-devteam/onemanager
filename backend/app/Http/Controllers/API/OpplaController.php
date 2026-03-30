<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpplaController extends Controller
{
    /**
     * Get all clients from Oppla PostgreSQL database
     */
    public function getClients()
    {
        try {
            $tableName = env('OPPLA_DB_TABLE', 'partners');
            
            // Query READ-ONLY al database Oppla
            $clients = DB::connection('oppla')
                ->table($tableName)
                ->select([
                    'id',
                    'email',
                    'ragione_sociale',
                    'piva',
                    'phone',
                    'is_active',
                    'created_at',
                    'updated_at'
                ])
                ->where('is_active', true)
                ->orderBy('ragione_sociale', 'asc')
                ->get()
                ->map(function ($client) {
                    return [
                        'id' => $client->id,
                        'ragione_sociale' => $client->ragione_sociale,
                        'email' => $client->email,
                        'piva' => $client->piva ?? null,
                        'phone' => $client->phone ?? null,
                        'type' => 'partner_oppla',
                        'is_active' => $client->is_active,
                        'source' => 'oppla_db',
                        'created_at' => $client->created_at,
                        'updated_at' => $client->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $clients,
                'total' => $clients->count(),
                'source' => 'oppla_postgresql'
            ]);

        } catch (\Exception $e) {
            Log::error('Oppla DB Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore connessione database Oppla',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test database connection
     */
    public function testConnection()
    {
        try {
            $tableName = env('OPPLA_DB_TABLE', 'partners');
            
            $stats = DB::connection('oppla')
                ->table($tableName)
                ->selectRaw('
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN email IS NOT NULL AND email != \'\' THEN 1 END) as clients_with_email,
                    COUNT(CASE WHEN phone IS NOT NULL AND phone != \'\' THEN 1 END) as clients_with_phone
                ')
                ->where('is_active', true)
                ->first();

            return response()->json([
                'success' => true,
                'connected' => true,
                'database' => 'oppla_postgresql',
                'stats' => [
                    'total_clients' => $stats->total_clients,
                    'clients_with_email' => $stats->clients_with_email,
                    'clients_with_phone' => $stats->clients_with_phone,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Oppla DB Connection Test Failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'connected' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
