<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class RestaurantController extends Controller
{
    /**
     * Ottieni tutti i ristoranti non assegnati
     */
    public function getUnassigned()
    {
        try {
            $restaurants = Restaurant::with('partner')
                ->whereNull('client_id')
                ->where('is_active', true)
                ->orderBy('nome')
                ->get()
                ->map(function($restaurant) {
                    return [
                        'id' => $restaurant->id,
                        'oppla_external_id' => $restaurant->oppla_external_id,
                        'nome' => $restaurant->nome,
                        'indirizzo' => $restaurant->indirizzo,
                        'citta' => $restaurant->citta,
                        'telefono' => $restaurant->telefono,
                        'email' => $restaurant->email,
                        'partner' => $restaurant->partner ? [
                            'id' => $restaurant->partner->id,
                            'nome' => $restaurant->partner->nome,
                            'cognome' => $restaurant->partner->cognome,
                            'email' => $restaurant->partner->email,
                            'telefono' => $restaurant->partner->telefono,
                        ] : null,
                        'oppla_sync_at' => $restaurant->oppla_sync_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $restaurants,
                'total' => $restaurants->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('[Restaurant] Errore recupero ristoranti non assegnati: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore recupero ristoranti',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assegna uno o più ristoranti a un titolare
     */
    public function assignToClient(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'restaurant_ids' => 'required|array|min:1',
            'restaurant_ids.*' => 'exists:restaurants,id',
        ]);

        try {
            $client = Client::findOrFail($validated['client_id']);
            
            $assigned = Restaurant::whereIn('id', $validated['restaurant_ids'])
                ->update(['client_id' => $client->id]);

            Log::info('[Restaurant] Ristoranti assegnati al titolare', [
                'client_id' => $client->id,
                'client_name' => $client->ragione_sociale,
                'restaurant_ids' => $validated['restaurant_ids'],
                'count' => $assigned,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Assegnati {$assigned} ristoranti a {$client->ragione_sociale}",
                'assigned' => $assigned,
            ]);
        } catch (\Exception $e) {
            Log::error('[Restaurant] Errore assegnazione ristoranti: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore assegnazione ristoranti',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rimuovi assegnazione di un ristorante da un titolare
     */
    public function unassignFromClient(Request $request, $restaurantId)
    {
        try {
            $restaurant = Restaurant::findOrFail($restaurantId);
            
            $previousClientId = $restaurant->client_id;
            $restaurant->update(['client_id' => null]);

            Log::info('[Restaurant] Ristorante rimosso dal titolare', [
                'restaurant_id' => $restaurant->id,
                'restaurant_name' => $restaurant->nome,
                'previous_client_id' => $previousClientId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ristorante rimosso dal titolare',
            ]);
        } catch (\Exception $e) {
            Log::error('[Restaurant] Errore rimozione assegnazione: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore rimozione assegnazione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sposta un ristorante da un titolare a un altro
     */
    public function reassignToClient(Request $request, $restaurantId)
    {
        $validated = $request->validate([
            'new_client_id' => 'required|exists:clients,id',
        ]);

        try {
            $restaurant = Restaurant::findOrFail($restaurantId);
            $oldClientId = $restaurant->client_id;
            
            $restaurant->update(['client_id' => $validated['new_client_id']]);
            
            $newClient = Client::find($validated['new_client_id']);

            Log::info('[Restaurant] Ristorante spostato a nuovo titolare', [
                'restaurant_id' => $restaurant->id,
                'restaurant_name' => $restaurant->nome,
                'old_client_id' => $oldClientId,
                'new_client_id' => $validated['new_client_id'],
                'new_client_name' => $newClient->ragione_sociale,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Ristorante assegnato a {$newClient->ragione_sociale}",
            ]);
        } catch (\Exception $e) {
            Log::error('[Restaurant] Errore riassegnazione ristorante: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore riassegnazione ristorante',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
