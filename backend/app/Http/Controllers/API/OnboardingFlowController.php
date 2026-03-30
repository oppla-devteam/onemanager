<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Restaurant;
use App\Models\Partner;
use App\Models\OnboardingSession;
use App\Models\FeeClass;
use App\Models\DeliveryZone;
use App\Services\ContractService;
use App\Services\OpplaIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class OnboardingFlowController extends Controller
{
    private ContractService $contractService;
    private OpplaIntegrationService $opplaService;

    public function __construct(
        ContractService $contractService,
        OpplaIntegrationService $opplaService
    ) {
        $this->contractService = $contractService;
        $this->opplaService = $opplaService;
    }

    /**
     * Step 1: Create client (titolare) and partner, then sync partner to Oppla.
     * Oppla automatically sends the invite email with password reset link.
     */
    public function storeClientAndPartner(Request $request)
    {
        // Validate partner fields (always required)
        $partnerRules = [
            'referent_nome' => 'required|string',
            'referent_cognome' => 'required|string',
            'referent_telefono' => 'required|string',
            'referent_email' => 'required|email',
        ];

        if ($request->has('existing_client_id')) {
            $validated = $request->validate(array_merge(
                ['existing_client_id' => 'required|exists:clients,id'],
                $partnerRules
            ));
            $client = Client::findOrFail($validated['existing_client_id']);
        } else {
            $validated = $request->validate(array_merge([
                'nome' => 'required|string',
                'cognome' => 'required|string',
                'email' => 'required|email',
                'telefono' => 'required|string',
                'ragione_sociale' => 'required|string',
                'piva' => 'required|string',
                'codice_fiscale' => 'nullable|string',
                'indirizzo' => 'required|string',
                'citta' => 'required|string',
                'provincia' => 'required|string|max:2',
                'cap' => 'required|string|max:5',
                'nazione' => 'nullable|string',
                'pec' => 'nullable|email',
                'sdi_code' => 'nullable|string',
            ], $partnerRules));
            $client = null;
        }

        DB::beginTransaction();
        try {
            // Create client if not using existing
            if (!$client) {
                $client = Client::create([
                    'guid' => (string) Str::uuid(),
                    'type' => 'partner_oppla',
                    'ragione_sociale' => $validated['ragione_sociale'],
                    'piva' => $validated['piva'],
                    'codice_fiscale' => $validated['codice_fiscale'] ?? null,
                    'email' => $validated['email'],
                    'phone' => $validated['telefono'],
                    'pec' => $validated['pec'] ?? null,
                    'sdi_code' => $validated['sdi_code'] ?? null,
                    'indirizzo' => $validated['indirizzo'],
                    'citta' => $validated['citta'],
                    'provincia' => $validated['provincia'],
                    'cap' => $validated['cap'],
                    'nazione' => $validated['nazione'] ?? 'IT',
                    'status' => 'active',
                    'is_active' => false, // Attivato a fine onboarding
                ]);
            }

            // Create or reuse partner (without restaurant_id)
            $partner = Partner::where('email', $validated['referent_email'])->first();

            if ($partner) {
                $partner->update([
                    'nome' => $validated['referent_nome'],
                    'cognome' => $validated['referent_cognome'],
                    'telefono' => $validated['referent_telefono'],
                    'is_active' => true,
                ]);
                Log::info('[Onboarding] Partner esistente riutilizzato', [
                    'partner_id' => $partner->id,
                    'email' => $partner->email,
                ]);
            } else {
                $partner = Partner::create([
                    'nome' => $validated['referent_nome'],
                    'cognome' => $validated['referent_cognome'],
                    'telefono' => $validated['referent_telefono'],
                    'email' => $validated['referent_email'],
                    'is_active' => true,
                ]);
                Log::info('[Onboarding] Nuovo partner creato', [
                    'partner_id' => $partner->id,
                    'email' => $partner->email,
                ]);
            }

            // Sync partner to Oppla immediately - triggers invite email
            $opplaPartnerId = null;
            $opplaError = null;
            if (!$partner->oppla_external_id) {
                try {
                    $opplaPartner = $this->opplaService->createPartner([
                        'nome' => $partner->nome,
                        'cognome' => $partner->cognome,
                        'email' => $partner->email,
                        'telefono' => $partner->telefono ?? '',
                    ]);

                    if ($opplaPartner && isset($opplaPartner['id'])) {
                        $partner->update([
                            'oppla_external_id' => $opplaPartner['id'],
                            'oppla_sync_at' => now(),
                        ]);
                        $opplaPartnerId = $opplaPartner['id'];
                        Log::info('[Onboarding] Partner synced to OPPLA - invite email sent', [
                            'local_id' => $partner->id,
                            'oppla_id' => $opplaPartner['id'],
                            'email' => $partner->email,
                        ]);
                    }
                } catch (\Exception $e) {
                    $opplaError = $e->getMessage();
                    Log::warning('[Onboarding] Failed to sync partner to OPPLA', [
                        'partner_id' => $partner->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $opplaPartnerId = $partner->oppla_external_id;
            }

            // Create onboarding session
            $session = OnboardingSession::create([
                'client_id' => $client->id,
                'user_id' => auth()->id(),
                'partner_id' => $partner->id,
                'step_client_partner_completed' => true,
                'current_step' => 2,
                'temp_data' => [
                    'owner' => $request->has('existing_client_id')
                        ? ['existing_client_id' => $client->id, 'ragione_sociale' => $client->ragione_sociale]
                        : $validated,
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Client e partner creati. Email di invito inviata da Oppla.',
                'data' => [
                    'session_id' => $session->id,
                    'client_id' => $client->id,
                    'partner_id' => $partner->id,
                    'oppla_partner_id' => $opplaPartnerId,
                    'next_step' => 2,
                ],
                'warnings' => $opplaError ? ['oppla_sync' => $opplaError] : [],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Onboarding] Errore Step 1: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 2 (GET): Check Stripe Connect status for the partner.
     */
    public function checkStripeStatus($sessionId)
    {
        $session = OnboardingSession::with('partner')->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'partner_id' => $session->partner_id,
                'partner_email' => $session->partner?->email,
                'oppla_partner_id' => $session->partner?->oppla_external_id,
                'stripe_confirmed' => $session->step_stripe_confirmed,
                'current_step' => $session->current_step,
            ],
        ]);
    }

    /**
     * Step 2 (POST): Manually confirm that the partner has completed Stripe Connect.
     */
    public function confirmStripe(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:onboarding_sessions,id',
        ]);

        $session = OnboardingSession::findOrFail($validated['session_id']);

        if (!$session->step_client_partner_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Step 1 (client + partner) non completato',
            ], 422);
        }

        $session->update([
            'step_stripe_confirmed' => true,
            'current_step' => 3,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stripe Connect confermato',
            'data' => [
                'session_id' => $session->id,
                'stripe_confirmed' => true,
                'next_step' => 3,
            ],
        ]);
    }

    /**
     * Step 3: Create restaurant, configure delivery/fees, sync to Oppla, and finalize.
     */
    public function createRestaurantAndFinalize(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:onboarding_sessions,id',
            // Restaurant
            'nome' => 'required|string',
            'category' => 'nullable|string',
            'description' => 'nullable|string',
            'telefono' => 'required|string',
            'indirizzo' => 'required|string',
            'citta' => 'required|string',
            'provincia' => 'required|string|max:2',
            'cap' => 'required|string|max:5',
            'zone' => 'nullable|string',
            // Delivery
            'delivery_management' => 'required|in:autonomous,oppla',
            'delivery_zones' => 'nullable|array',
            'delivery_zones.*' => 'exists:delivery_zones,id',
            'autonomous_zones' => 'nullable|array',
            'autonomous_zones.*.zone_name' => 'required_if:delivery_management,autonomous|string',
            'autonomous_zones.*.price' => 'required_if:delivery_management,autonomous|numeric',
            // Fees
            'best_price' => 'required|boolean',
            'activation_fee' => 'nullable|numeric|min:0',
            // Cover (optional)
            'logo_url' => 'nullable|url',
            'foto_url' => 'nullable|url',
            'cover_opacity' => 'nullable|integer|min:0|max:100',
            // Override
            'skip_stripe_check' => 'nullable|boolean',
        ]);

        $session = OnboardingSession::with(['client', 'partner'])->findOrFail($validated['session_id']);

        // Guard: Step 1 must be completed
        if (!$session->step_client_partner_completed) {
            return response()->json([
                'success' => false,
                'message' => 'Step 1 (client + partner) non completato',
            ], 422);
        }

        // Soft guard: Stripe should be confirmed (warn if not, allow override)
        $stripeWarning = null;
        if (!$session->step_stripe_confirmed && !($validated['skip_stripe_check'] ?? false)) {
            $stripeWarning = 'Stripe Connect non ancora confermato. Usa skip_stripe_check=true per procedere comunque.';
            return response()->json([
                'success' => false,
                'message' => $stripeWarning,
            ], 422);
        }
        if (!$session->step_stripe_confirmed) {
            $stripeWarning = 'Proceduto senza conferma Stripe Connect';
        }

        DB::beginTransaction();
        try {
            // Create restaurant
            $deliveryData = [];
            if ($validated['delivery_management'] === 'oppla') {
                $deliveryData = $validated['delivery_zones'] ?? [];
            } else {
                $deliveryData = $validated['autonomous_zones'] ?? [];
            }

            $restaurant = Restaurant::create([
                'client_id' => $session->client_id,
                'nome' => $validated['nome'],
                'category' => $validated['category'] ?? null,
                'description' => $validated['description'] ?? null,
                'telefono' => $validated['telefono'],
                'indirizzo' => $validated['indirizzo'],
                'citta' => $validated['citta'],
                'provincia' => $validated['provincia'],
                'cap' => $validated['cap'],
                'zone' => $validated['zone'] ?? null,
                'delivery_management' => $validated['delivery_management'],
                'delivery_zones' => $deliveryData,
                'is_active' => false,
            ]);

            // Link partner to restaurant
            $session->partner->update([
                'restaurant_id' => $restaurant->id,
            ]);

            // Configure fees
            $deliveryType = $validated['delivery_management'] === 'autonomous' ? 'autonomous' : 'managed';
            $bestPrice = $validated['best_price'];

            $feeClass = FeeClass::where('delivery_type', $deliveryType)
                ->where('best_price', $bestPrice)
                ->where('is_active', true)
                ->first();

            if (!$feeClass) {
                $this->seedFeeClasses();
                $feeClass = FeeClass::where('delivery_type', $deliveryType)
                    ->where('best_price', $bestPrice)
                    ->where('is_active', true)
                    ->first();
            }

            if ($feeClass) {
                $restaurant->update([
                    'fee_class_id' => $feeClass->id,
                    'best_price' => $bestPrice,
                ]);
            }

            // Generate cover if URLs provided
            if (!empty($validated['logo_url']) && !empty($validated['foto_url'])) {
                try {
                    $logoContents = file_get_contents($validated['logo_url']);
                    $fotoContents = file_get_contents($validated['foto_url']);

                    if ($logoContents && $fotoContents) {
                        $logoExt = pathinfo(parse_url($validated['logo_url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                        $logoFilename = 'restaurants/logos/' . Str::uuid() . '.' . $logoExt;
                        Storage::disk('public')->put($logoFilename, $logoContents);

                        $fotoExt = pathinfo(parse_url($validated['foto_url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                        $fotoFilename = 'restaurants/photos/' . Str::uuid() . '.' . $fotoExt;
                        Storage::disk('public')->put($fotoFilename, $fotoContents);

                        $opacity = $validated['cover_opacity'] ?? 50;
                        $coverPath = $this->generateCover(
                            storage_path('app/public/' . $logoFilename),
                            storage_path('app/public/' . $fotoFilename),
                            $opacity,
                            $restaurant->id
                        );

                        $restaurant->update([
                            'logo_path' => $logoFilename,
                            'foto_path' => $fotoFilename,
                            'cover_path' => $coverPath ?? $fotoFilename,
                            'cover_opacity' => $opacity,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('[Onboarding] Cover generation failed, continuing', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Sync restaurant to Oppla
            $opplaResults = ['restaurant_synced' => false, 'errors' => []];
            try {
                $opplaRestaurant = $this->opplaService->createRestaurant([
                    'nome' => $restaurant->nome,
                    'slug' => Str::slug($restaurant->nome),
                    'telefono' => $restaurant->telefono ?? '',
                    'indirizzo' => $restaurant->indirizzo ?? '',
                    'citta' => $restaurant->citta ?? '',
                    'provincia' => $restaurant->provincia ?? '',
                    'cap' => $restaurant->cap ?? '',
                    'description' => $restaurant->description ?? 'Restaurant created via Oppla One Manager onboarding',
                    'preparation_time_minutes' => 30,
                    'accepts_deliveries' => true,
                    'accepts_pickups' => false,
                    'accepts_cash' => true,
                    'delivery_management' => $restaurant->delivery_management ?? 'oppla',
                ]);

                if ($opplaRestaurant && isset($opplaRestaurant['id'])) {
                    $restaurant->update([
                        'oppla_external_id' => $opplaRestaurant['id'],
                        'oppla_sync_at' => now(),
                    ]);
                    $opplaResults['restaurant_synced'] = true;
                    Log::info('[Onboarding] Restaurant synced to OPPLA', [
                        'local_id' => $restaurant->id,
                        'oppla_id' => $opplaRestaurant['id'],
                    ]);
                }
            } catch (\Exception $e) {
                $opplaResults['errors'][] = "Restaurant: " . $e->getMessage();
                Log::warning('[Onboarding] Failed to sync restaurant to OPPLA', [
                    'restaurant_id' => $restaurant->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Activate restaurant and client
            $restaurant->update(['is_active' => true]);
            $session->client->update([
                'status' => 'active',
                'activation_date' => now(),
            ]);

            // Generate contract
            $contract = null;
            $contractError = null;
            try {
                $activationFee = $validated['activation_fee'] ?? 150.00;
                $tempData = $session->temp_data ?? [];

                $feeData = [
                    'activation_fee' => $activationFee,
                    'pickup_fee' => $tempData['pickup_fee'] ?? 12.00,
                    'main_service_fee' => $tempData['main_service_fee'] ?? 2.98,
                    'rejected_order_fee' => $tempData['rejected_order_fee'] ?? 1.49,
                    'manual_entry_fee' => $tempData['manual_entry_fee'] ?? 1.49,
                    'delivery_subscription' => $tempData['delivery_subscription'] ?? 24.00,
                    'equipment_provided' => $tempData['equipment_provided'] ?? true,
                ];

                $restaurantData = [
                    'nome' => $restaurant->nome,
                    'indirizzo' => ($restaurant->indirizzo ?? '') . ', ' .
                                  ($restaurant->citta ?? '') . ' ' .
                                  ($restaurant->provincia ?? ''),
                    'best_price' => $bestPrice,
                ];

                $contract = $this->contractService->createFromOnboarding(
                    $session->client_id,
                    $restaurantData,
                    $feeData
                );

                Log::info('[Onboarding] Contratto generato', [
                    'contract_id' => $contract->id,
                    'client_id' => $session->client_id,
                ]);
            } catch (\Exception $e) {
                $contractError = $e->getMessage();
                Log::error('[Onboarding] Errore generazione contratto', [
                    'error' => $e->getMessage(),
                    'client_id' => $session->client_id,
                ]);
            }

            // Complete session
            $session->update([
                'step_restaurant_completed' => true,
                'current_step' => 3,
                'status' => 'completed',
                'completed_at' => now(),
                'temp_data' => array_merge($session->temp_data ?? [], [
                    'restaurant_id' => $restaurant->id,
                    'fee_configuration' => [
                        'best_price' => $bestPrice,
                        'fee_class_id' => $feeClass?->id,
                    ],
                    'activation_fee' => $validated['activation_fee'] ?? 150.00,
                ]),
            ]);

            DB::commit();

            $warnings = [];
            if ($stripeWarning) $warnings['stripe'] = $stripeWarning;
            if ($contractError) $warnings['contract'] = $contractError;
            if (!empty($opplaResults['errors'])) $warnings['oppla'] = $opplaResults['errors'];

            return response()->json([
                'success' => true,
                'message' => 'Onboarding completato con successo!',
                'data' => [
                    'client' => $session->client,
                    'restaurant' => $restaurant,
                    'partner' => $session->partner,
                    'contract' => $contract ? [
                        'id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'status' => $contract->status,
                    ] : null,
                    'fee_class' => $feeClass,
                    'oppla_sync' => $opplaResults,
                ],
                'warnings' => $warnings,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[Onboarding] Errore Step 3: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione del ristorante',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available delivery zones
     */
    public function getDeliveryZones()
    {
        $zones = DeliveryZone::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Get onboarding session status
     */
    public function getSessionStatus($sessionId)
    {
        $session = OnboardingSession::with(['client', 'user', 'partner'])->findOrFail($sessionId);

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'progress' => $session->getProgressPercentage(),
                'partner' => $session->partner,
                'restaurants' => Restaurant::where('client_id', $session->client_id)->get(),
            ],
        ]);
    }

    /**
     * Genera cover image (1200x700) con foto sfondo, logo centrato e overlay con opacita
     */
    private function generateCover(string $logoPath, string $fotoPath, int $opacity, int $restaurantId): ?string
    {
        try {
            $manager = new ImageManager(new Driver());

            $background = $manager->read($fotoPath);
            $background->resize(1200, 700);

            $overlay = $manager->create(1200, 700)->fill('000000');

            $logo = $manager->read($logoPath);
            $logo->scale(400, 400);

            $background->place($overlay, 'center', opacity: (100 - $opacity));
            $background->place($logo, 'center');

            $coverFilename = 'restaurants/covers/cover_' . $restaurantId . '_' . time() . '.jpg';
            $coverFullPath = storage_path('app/public/' . $coverFilename);

            $coverDir = dirname($coverFullPath);
            if (!file_exists($coverDir)) {
                mkdir($coverDir, 0755, true);
            }

            $background->save($coverFullPath, quality: 90);

            return $coverFilename;
        } catch (\Exception $e) {
            Log::error('[Onboarding] Errore generazione cover: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea le FeeClass di default se non esistono
     */
    private function seedFeeClasses(): void
    {
        $feeClasses = [
            [
                'name' => 'Autonoma - Miglior Prezzo',
                'description' => 'Ristorante gestisce le proprie consegne con opzione miglior prezzo',
                'delivery_type' => 'autonomous',
                'best_price' => true,
                'monthly_fee' => 29.00,
                'order_fee_percentage' => 8.00,
                'order_fee_fixed' => 0.30,
                'delivery_base_fee' => 0.00,
                'delivery_km_fee' => 0.00,
                'payment_processing_fee' => 1.50,
                'platform_fee' => 5.00,
                'is_active' => true,
            ],
            [
                'name' => 'Autonoma - Standard',
                'description' => 'Ristorante gestisce le proprie consegne senza opzione miglior prezzo',
                'delivery_type' => 'autonomous',
                'best_price' => false,
                'monthly_fee' => 49.00,
                'order_fee_percentage' => 12.00,
                'order_fee_fixed' => 0.50,
                'delivery_base_fee' => 0.00,
                'delivery_km_fee' => 0.00,
                'payment_processing_fee' => 2.00,
                'platform_fee' => 8.00,
                'is_active' => true,
            ],
            [
                'name' => 'Gestita FLA - Miglior Prezzo',
                'description' => 'Consegne gestite da FLA con rider proprietari e opzione miglior prezzo',
                'delivery_type' => 'managed',
                'best_price' => true,
                'monthly_fee' => 99.00,
                'order_fee_percentage' => 5.00,
                'order_fee_fixed' => 0.20,
                'delivery_base_fee' => 2.50,
                'delivery_km_fee' => 0.80,
                'payment_processing_fee' => 1.20,
                'platform_fee' => 3.00,
                'is_active' => true,
            ],
            [
                'name' => 'Gestita FLA - Premium',
                'description' => 'Consegne gestite da FLA con rider proprietari, massima visibilita',
                'delivery_type' => 'managed',
                'best_price' => false,
                'monthly_fee' => 149.00,
                'order_fee_percentage' => 10.00,
                'order_fee_fixed' => 0.40,
                'delivery_base_fee' => 3.00,
                'delivery_km_fee' => 1.00,
                'payment_processing_fee' => 1.80,
                'platform_fee' => 6.00,
                'is_active' => true,
            ],
        ];

        foreach ($feeClasses as $feeClass) {
            FeeClass::firstOrCreate(
                [
                    'delivery_type' => $feeClass['delivery_type'],
                    'best_price' => $feeClass['best_price'],
                    'name' => $feeClass['name'],
                ],
                $feeClass
            );
        }
    }
}
