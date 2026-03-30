<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Restaurant;
use App\Models\Client;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * Display a listing of partners with their restaurants
     */
    public function index(Request $request)
    {
        $query = Partner::with(['restaurant.client'])
            ->orderBy('created_at', 'desc');

        // Filter by client_id if provided
        if ($request->has('client_id')) {
            $clientId = $request->client_id;
            $query->whereHas('restaurant', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        // Filter by has_client (partners with or without assigned client)
        if ($request->has('has_client')) {
            $hasClient = filter_var($request->has_client, FILTER_VALIDATE_BOOLEAN);
            if ($hasClient) {
                $query->whereHas('restaurant', function ($q) {
                    $q->whereNotNull('client_id');
                });
            } else {
                $query->whereHas('restaurant', function ($q) {
                    $q->whereNull('client_id');
                });
            }
        }

        $partners = $query->get();

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified partner
     */
    public function show($id)
    {
        $partner = Partner::with(['restaurant.client'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $partner
        ]);
    }

    /**
     * Update the specified partner
     */
    public function update(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'sometimes|string',
            'cognome' => 'sometimes|string',
            'email' => 'sometimes|email|unique:partners,email,' . $id,
            'telefono' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $partner->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Partner aggiornato con successo',
            'data' => $partner->load('restaurant.client')
        ]);
    }

    /**
     * Assign client (titolare) to partner's restaurant
     */
    public function assignClient(Request $request, $id)
    {
        $partner = Partner::with('restaurant')->findOrFail($id);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        // Se il partner non ha un ristorante, crea un ristorante fittizio
        if (!$partner->restaurant) {
            $restaurant = \App\Models\Restaurant::create([
                'nome' => 'Ristorante di ' . $partner->nome . ' ' . $partner->cognome,
                'indirizzo' => 'Da completare',
                'citta' => 'Da completare',
                'provincia' => 'LI',
                'cap' => '00000',
                'telefono' => $partner->telefono,
                'email' => $partner->email,
                'client_id' => $validated['client_id'],
                'is_active' => true,
            ]);

            // Associa il ristorante al partner
            $partner->update(['restaurant_id' => $restaurant->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Titolare assegnato con successo. Ristorante creato automaticamente.',
                'data' => $partner->load('restaurant.client')
            ]);
        }

        // Se il ristorante esiste, aggiorna solo il client_id
        $partner->restaurant->update([
            'client_id' => $validated['client_id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Titolare assegnato con successo',
            'data' => $partner->load('restaurant.client')
        ]);
    }

    /**
     * Remove client assignment from partner's restaurant
     */
    public function unassignClient($id)
    {
        $partner = Partner::with('restaurant')->findOrFail($id);

        if (!$partner->restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Partner non ha un ristorante associato'
            ], 400);
        }

        $partner->restaurant->update([
            'client_id' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Titolare rimosso con successo',
            'data' => $partner->load('restaurant.client')
        ]);
    }

    /**
     * Get statistics about partners
     */
    public function stats()
    {
        $total = Partner::count();
        $withClient = Partner::whereHas('restaurant', function ($q) {
            $q->whereNotNull('client_id');
        })->count();
        $withoutClient = $total - $withClient;
        $withEmail = Partner::whereNotNull('email')->count();
        $withPhone = Partner::whereNotNull('telefono')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_partners' => $total,
                'partners_with_client' => $withClient,
                'partners_without_client' => $withoutClient,
                'partners_with_email' => $withEmail,
                'partners_with_phone' => $withPhone,
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $partner = Partner::with('restaurant')->findOrFail($id);

        // Se il partner ha un ristorante con un client assegnato, rimuovi prima l'assegnazione
        if ($partner->restaurant && $partner->restaurant->client_id) {
            $partner->restaurant->update(['client_id' => null]);
        }

        // Elimina il partner
        $partner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partner eliminato con successo'
        ]);
    }
}
