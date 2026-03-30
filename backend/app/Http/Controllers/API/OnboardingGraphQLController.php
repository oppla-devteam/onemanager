<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\OpplaGraphQLService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Onboarding Controller - Hybrid Architecture
 * 
 * - Titolari (Clients): Saved in local DB (MySQL/SQLite)
 * - Ristoranti e Partner: Created via OPPLA GraphQL API
 */
class OnboardingGraphQLController extends Controller
{
    private OpplaGraphQLService $opplaApi;

    public function __construct(OpplaGraphQLService $opplaApi)
    {
        $this->opplaApi = $opplaApi;
    }

    /**
     * Complete onboarding process
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titolare' => 'required|array',
            'titolare.nome' => 'required|string',
            'titolare.cognome' => 'required|string',
            'titolare.telefono' => 'required|string',
            'titolare.email' => 'required|email',
            'titolare.ragione_sociale' => 'required|string',
            'titolare.piva' => 'required|string',
            'titolare.indirizzo_fatturazione' => 'required|string',
            'titolare.codice_sdi' => 'nullable|string',
            'titolare.pec' => 'nullable|email',
            'restaurants' => 'required|array|min:1',
            'restaurants.*.nome' => 'required|string',
            'restaurants.*.indirizzo' => 'nullable|string',
            'restaurants.*.citta' => 'nullable|string',
            'restaurants.*.provincia' => 'nullable|string|max:2',
            'restaurants.*.cap' => 'nullable|string|max:5',
            'restaurants.*.telefono' => 'nullable|string',
            'restaurants.*.email' => 'nullable|email',
            'restaurants.*.piva' => 'nullable|string',
            'restaurants.*.codice_fiscale' => 'nullable|string',
            'restaurants.*.logo' => 'nullable|file|image|max:5120',
            'restaurants.*.foto' => 'nullable|file|image|max:5120',
            'restaurants.*.cover_opacity' => 'nullable|integer|min:0|max:100',
            'restaurants.*.delivery_management' => 'required|in:oppla,autonomous',
            'restaurants.*.delivery_zones' => 'nullable|array',
            'partners' => 'required|array|min:1',
            'partners.*.nome' => 'required|string',
            'partners.*.cognome' => 'required|string',
            'partners.*.email' => 'required|email',
            'partners.*.telefono' => 'required|string',
            'partners.*.password' => 'required|string|min:8',
        ]);

        // Validate SDI or PEC
        if (empty($validated['titolare']['codice_sdi']) && empty($validated['titolare']['pec'])) {
            return response()->json([
                'success' => false,
                'message' => 'Almeno uno tra Codice SDI e PEC è obbligatorio'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Create Client (Titolare) in LOCAL database
            $client = Client::create([
                'ragione_sociale' => $validated['titolare']['ragione_sociale'],
                'type' => 'partner_oppla',
                'email' => $validated['titolare']['email'],
                'piva' => $validated['titolare']['piva'],
                'phone' => $validated['titolare']['telefono'],
                'codice_fiscale' => $validated['titolare']['codice_sdi'] ?? null,
                'indirizzo' => $validated['titolare']['indirizzo_fatturazione'],
                'is_active' => true,
                'source' => 'onboarding',
            ]);

            Log::info('Client created locally', ['client_id' => $client->id]);

            $restaurants = [];
            $partners = [];

            // 2. Create Restaurants and Partners via OPPLA GraphQL API
            foreach ($validated['restaurants'] as $index => $restaurantData) {
                // Upload images to local storage (optional - could upload to OPPLA CDN later)
                $logoPath = null;
                $fotoPath = null;

                if (isset($restaurantData['logo'])) {
                    $logoPath = $restaurantData['logo']->store('restaurants/logos', 'public');
                }

                if (isset($restaurantData['foto'])) {
                    $fotoPath = $restaurantData['foto']->store('restaurants/photos', 'public');
                }

                // Create restaurant via OPPLA API
                try {
                    $opplaRestaurant = $this->opplaApi->createRestaurant([
                        'nome' => $restaurantData['nome'],
                        'indirizzo' => $restaurantData['indirizzo'] ?? null,
                        'citta' => $restaurantData['citta'] ?? null,
                        'provincia' => $restaurantData['provincia'] ?? null,
                        'cap' => $restaurantData['cap'] ?? null,
                        'telefono' => $restaurantData['telefono'] ?? null,
                        'email' => $restaurantData['email'] ?? null,
                        'piva' => $restaurantData['piva'] ?? null,
                        'codice_fiscale' => $restaurantData['codice_fiscale'] ?? null,
                        'client_id' => $client->id, // Link to local client
                        'delivery_management' => $restaurantData['delivery_management'],
                        'delivery_zones' => $restaurantData['delivery_zones'] ?? [],
                    ]);

                    $restaurants[] = $opplaRestaurant;

                    Log::info('Restaurant created via OPPLA API', [
                        'oppla_id' => $opplaRestaurant['id'],
                        'nome' => $opplaRestaurant['nome']
                    ]);

                    // Upload logo to OPPLA if provided
                    if ($logoPath) {
                        $this->opplaApi->uploadRestaurantLogo(
                            $opplaRestaurant['id'], 
                            storage_path('app/public/' . $logoPath)
                        );
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to create restaurant via API', [
                        'error' => $e->getMessage(),
                        'restaurant' => $restaurantData['nome']
                    ]);
                    throw new \Exception('Errore creazione ristorante: ' . $e->getMessage());
                }

                // Create partner via OPPLA API
                if (isset($validated['partners'][$index])) {
                    $partnerData = $validated['partners'][$index];
                    
                    try {
                        // Create user in local DB for authentication
                        $user = User::create([
                            'name' => $partnerData['nome'] . ' ' . $partnerData['cognome'],
                            'email' => $partnerData['email'],
                            'password' => Hash::make($partnerData['password']),
                            'email_verified_at' => now(),
                        ]);

                        // Assign partner role
                        $user->assignRole('partner');

                        Log::info('Partner user created locally', [
                            'user_id' => $user->id,
                            'email' => $user->email
                        ]);

                        // Create partner record in OPPLA via API
                        $opplaPartner = $this->opplaApi->createPartner([
                            'nome' => $partnerData['nome'],
                            'cognome' => $partnerData['cognome'],
                            'email' => $partnerData['email'],
                            'telefono' => $partnerData['telefono'],
                            'restaurant_id' => $opplaRestaurant['id'],
                            'password' => $partnerData['password'], // For OPPLA platform login
                        ]);

                        $partners[] = $opplaPartner;

                        Log::info('Partner created via OPPLA API', [
                            'oppla_id' => $opplaPartner['id'],
                            'email' => $opplaPartner['email']
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Failed to create partner', [
                            'error' => $e->getMessage(),
                            'partner' => $partnerData['email']
                        ]);
                        throw new \Exception('Errore creazione partner: ' . $e->getMessage());
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completato con successo! Ristoranti e partner creati su OPPLA.',
                'data' => [
                    'client' => $client,
                    'restaurants' => $restaurants,
                    'partners' => $partners,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Onboarding failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'onboarding: ' . $e->getMessage()
            ], 500);
        }
    }
}
