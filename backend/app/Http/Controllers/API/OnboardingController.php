<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Restaurant;
use App\Models\Partner;
use App\Models\User;
use App\Services\OpplaIntegrationService;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class OnboardingController extends Controller
{
    private OpplaIntegrationService $opplaService;
    private ContractService $contractService;

    public function __construct(
        OpplaIntegrationService $opplaService,
        ContractService $contractService
    ) {
        $this->opplaService = $opplaService;
        $this->contractService = $contractService;
    }

    /**
     * Complete onboarding process: create client, restaurants, and partners
     * 
     * Strategy:
     * - Client (Titolare): Created in LOCAL database
     * - Restaurant: Created via OPPLA Filament panel (API doesn't support creation)
     * - Partner: User created locally + Partner record in OPPLA via Filament
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
            'restaurants.*.delivery_zones.*' => 'nullable|string',
            'partners' => 'required|array|min:1',
            'partners.*.nome' => 'required|string',
            'partners.*.cognome' => 'required|string',
            'partners.*.email' => 'required|email',
            'partners.*.telefono' => 'required|string',
            'partners.*.password' => 'nullable|string|min:8',
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
            // 1. Create Client (Titolare)
            $client = Client::create([
                'ragione_sociale' => $validated['titolare']['ragione_sociale'],
                'type' => 'partner_oppla',
                'email' => $validated['titolare']['email'],
                'piva' => $validated['titolare']['piva'],
                'phone' => $validated['titolare']['telefono'],
                'codice_fiscale' => $validated['titolare']['codice_sdi'] ?? null,
                'indirizzo' => $validated['titolare']['indirizzo_fatturazione'],
                'is_active' => true,
                'source' => 'manual',
            ]);

            $restaurants = [];
            $partners = [];

            // 2. Create Restaurants via OPPLA Filament Panel
            foreach ($validated['restaurants'] as $index => $restaurantData) {
                // Upload logo and foto locally
                $logoPath = null;
                if (isset($restaurantData['logo'])) {
                    $logoPath = $restaurantData['logo']->store('restaurants/logos', 'public');
                }

                $fotoPath = null;
                if (isset($restaurantData['foto'])) {
                    $fotoPath = $restaurantData['foto']->store('restaurants/photos', 'public');
                }

                try {
                    // Create restaurant in OPPLA via Filament panel
                    $opplaRestaurant = $this->opplaService->createRestaurant([
                        'nome' => $restaurantData['nome'],
                        'slug' => Str::slug($restaurantData['nome']),
                        'telefono' => $restaurantData['telefono'] ?? '',
                        'indirizzo' => $restaurantData['indirizzo'] ?? '',
                        'description' => 'Restaurant created via Oppla One Manager onboarding',
                        'preparation_time_minutes' => 30,
                        'accepts_deliveries' => true,
                        'accepts_pickups' => false,
                        'accepts_cash' => true,
                        'delivery_management' => $restaurantData['delivery_management'],
                    ]);

                    if (!$opplaRestaurant) {
                        throw new \Exception('Failed to create restaurant in OPPLA');
                    }

                    // Store restaurant reference locally with OPPLA ID
                    $restaurant = Restaurant::create([
                        'client_id' => $client->id,
                        'oppla_external_id' => $opplaRestaurant['id'] ?? null,
                        'nome' => $restaurantData['nome'],
                        'indirizzo' => $restaurantData['indirizzo'] ?? null,
                        'citta' => $restaurantData['citta'] ?? null,
                        'provincia' => $restaurantData['provincia'] ?? null,
                        'cap' => $restaurantData['cap'] ?? null,
                        'telefono' => $restaurantData['telefono'] ?? null,
                        'email' => $restaurantData['email'] ?? null,
                        'piva' => $restaurantData['piva'] ?? null,
                        'codice_fiscale' => $restaurantData['codice_fiscale'] ?? null,
                        'logo_path' => $logoPath,
                        'foto_path' => $fotoPath,
                        'cover_opacity' => $restaurantData['cover_opacity'] ?? 50,
                        'delivery_management' => $restaurantData['delivery_management'],
                        'delivery_zones' => $restaurantData['delivery_zones'] ?? null,
                        'is_active' => true,
                        'oppla_sync_at' => now(),
                    ]);

                    Log::info('Restaurant created', [
                        'local_id' => $restaurant->id,
                        'oppla_id' => $opplaRestaurant['id'] ?? null,
                        'nome' => $restaurant->nome
                    ]);

                } catch (\Exception $e) {
                    Log::error('Restaurant creation failed', [
                        'error' => $e->getMessage(),
                        'restaurant' => $restaurantData['nome']
                    ]);

                    // Fallback: Create locally without OPPLA sync
                    $restaurant = Restaurant::create([
                        'client_id' => $client->id,
                        'nome' => $restaurantData['nome'],
                        'indirizzo' => $restaurantData['indirizzo'] ?? null,
                        'citta' => $restaurantData['citta'] ?? null,
                        'provincia' => $restaurantData['provincia'] ?? null,
                        'cap' => $restaurantData['cap'] ?? null,
                        'telefono' => $restaurantData['telefono'] ?? null,
                        'email' => $restaurantData['email'] ?? null,
                        'piva' => $restaurantData['piva'] ?? null,
                        'codice_fiscale' => $restaurantData['codice_fiscale'] ?? null,
                        'logo_path' => $logoPath,
                        'foto_path' => $fotoPath,
                        'cover_opacity' => $restaurantData['cover_opacity'] ?? 50,
                        'delivery_management' => $restaurantData['delivery_management'],
                        'delivery_zones' => $restaurantData['delivery_zones'] ?? null,
                        'is_active' => true,
                    ]);

                    Log::warning('Restaurant created locally only (OPPLA sync failed)', [
                        'local_id' => $restaurant->id
                    ]);
                }

                // Generate cover if logo and foto are provided
                if ($logoPath && $fotoPath) {
                    $coverPath = $this->generateCover(
                        storage_path('app/public/' . $logoPath),
                        storage_path('app/public/' . $fotoPath),
                        $restaurantData['cover_opacity'] ?? 50,
                        $restaurant->id
                    );
                    $restaurant->update(['cover_path' => $coverPath]);
                }

                $restaurants[] = $restaurant;

                // 3. Create partner for this restaurant
                if (isset($validated['partners'][$index])) {
                    $partnerData = $validated['partners'][$index];
                    
                    try {
                        // Create user locally for authentication
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

                        // Create partner in OPPLA via Filament
                        $opplaPartner = $this->opplaService->createPartner([
                            'nome' => $partnerData['nome'],
                            'cognome' => $partnerData['cognome'],
                            'email' => $partnerData['email'],
                            'telefono' => $partnerData['telefono'],
                            'password' => $partnerData['password'],
                            'restaurant_id' => $restaurant->oppla_external_id ?? null,
                        ]);

                        // Create partner record locally
                        $partner = Partner::create([
                            'oppla_external_id' => $opplaPartner['id'] ?? null,
                            'nome' => $partnerData['nome'],
                            'cognome' => $partnerData['cognome'],
                            'email' => $partnerData['email'],
                            'telefono' => $partnerData['telefono'],
                            'restaurant_id' => $restaurant->id,
                            'user_id' => $user->id,
                            'is_active' => true,
                            'oppla_sync_at' => $opplaPartner ? now() : null,
                        ]);

                        $partners[] = $partner;

                        if ($opplaPartner) {
                            Log::info('Partner synced to OPPLA', [
                                'local_id' => $partner->id,
                                'oppla_id' => $opplaPartner['id']
                            ]);
                        } else {
                            Log::warning('Partner created locally only (OPPLA sync failed)');
                        }

                    } catch (\Exception $e) {
                        Log::error('Partner creation failed', [
                            'error' => $e->getMessage(),
                            'partner' => $partnerData['email']
                        ]);
                        throw $e;
                    }
                }
            }
            
            // 4. Genera contratto automaticamente per il primo ristorante
            $contract = null;
            try {
                if (!empty($restaurants)) {
                    $firstRestaurant = $restaurants[0];
                    
                    // Prepara dati fee (usa valori di default se non specificati)
                    $feeData = [
                        'activation_fee' => $validated['restaurants'][0]['activation_fee'] ?? 150.00,
                        'pickup_fee' => $validated['restaurants'][0]['pickup_fee'] ?? 12.00,
                        'main_service_fee' => $validated['restaurants'][0]['main_service_fee'] ?? 2.98,
                        'rejected_order_fee' => $validated['restaurants'][0]['rejected_order_fee'] ?? 1.49,
                        'manual_entry_fee' => $validated['restaurants'][0]['manual_entry_fee'] ?? 1.49,
                        'delivery_subscription' => $validated['restaurants'][0]['delivery_subscription'] ?? 24.00,
                        'equipment_provided' => $validated['restaurants'][0]['equipment_provided'] ?? true,
                    ];
                    
                    $restaurantData = [
                        'nome' => $firstRestaurant->nome,
                        'indirizzo' => $firstRestaurant->indirizzo . ', ' . $firstRestaurant->citta . ' ' . $firstRestaurant->provincia,
                        'best_price' => $validated['restaurants'][0]['best_price'] ?? false,
                    ];
                    
                    $contract = $this->contractService->createFromOnboarding(
                        $client->id,
                        $restaurantData,
                        $feeData
                    );
                    
                    Log::info('Contratto generato automaticamente', [
                        'contract_id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'client_id' => $client->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Errore generazione contratto onboarding', [
                    'error' => $e->getMessage(),
                    'client_id' => $client->id,
                ]);
                // Non bloccare l'onboarding se fallisce la generazione contratto
            }

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completato con successo',
                'data' => [
                    'client' => $client,
                    'restaurants' => $restaurants,
                    'partners' => $partners,
                    'contract' => $contract ? [
                        'id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'status' => $contract->status,
                        'pdf_url' => $contract->pdf_path ? url('storage/' . $contract->pdf_path) : null,
                    ] : null
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'onboarding: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate cover image (1200x700) with background photo, logo centered, and opacity
     */
    private function generateCover(string $logoPath, string $fotoPath, int $opacity, int $restaurantId): ?string
    {
        try {
            $manager = new ImageManager(new Driver());

            // Load background photo
            $background = $manager->read($fotoPath);
            $background->resize(1200, 700);

            // Apply opacity to background
            $background->reduceColors(255);
            
            // Create semi-transparent overlay
            $overlay = $manager->create(1200, 700)->fill('000000');
            
            // Load logo
            $logo = $manager->read($logoPath);
            
            // Resize logo to fit (max 400x400 centered)
            $logo->scale(400, 400);

            // Calculate centered position for logo
            $logoX = (1200 - $logo->width()) / 2;
            $logoY = (700 - $logo->height()) / 2;

            // Composite: background + overlay with opacity + logo
            $background->place($overlay, 'center', opacity: (100 - $opacity));
            $background->place($logo, 'center');

            // Save cover
            $coverFilename = 'restaurants/covers/cover_' . $restaurantId . '_' . time() . '.jpg';
            $coverFullPath = storage_path('app/public/' . $coverFilename);
            
            // Ensure directory exists
            $coverDir = dirname($coverFullPath);
            if (!file_exists($coverDir)) {
                mkdir($coverDir, 0755, true);
            }

            $background->save($coverFullPath, quality: 90);

            return $coverFilename;

        } catch (\Exception $e) {
            Log::error('Error generating cover: ' . $e->getMessage());
            return null;
        }
    }
}

