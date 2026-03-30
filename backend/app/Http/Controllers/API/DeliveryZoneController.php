<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use App\Services\OpplaIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DeliveryZoneController extends Controller
{
    private OpplaIntegrationService $opplaService;

    public function __construct(OpplaIntegrationService $opplaService)
    {
        $this->opplaService = $opplaService;
    }
    /**
     * Get all delivery zones from local database
     */
    public function index()
    {
        try {
            $zones = DeliveryZone::where('is_active', true)
                ->orderBy('city')
                ->orderBy('name')
                ->get();

            // Ottieni l'ultima sincronizzazione
            $lastSync = Cache::get('delivery_zones_last_sync');

            return response()->json([
                'success' => true,
                'data' => $zones->map(function($zone) {
                    return [
                        'id' => $zone->id,
                        'oppla_id' => $zone->oppla_id,
                        'name' => $zone->name,
                        'city' => $zone->city,
                        'description' => $zone->description,
                        'postal_codes' => $zone->postal_codes ?? [],
                        'price_ranges' => $zone->price_ranges ?? [],
                        'geometry' => $zone->geometry,
                        'color' => $zone->color ?? '#3b82f6',
                        'source' => $zone->source ?? 'manual',
                        'label' => "{$zone->name} - {$zone->city}",
                        'has_geometry' => $zone->hasGeometry(),
                    ];
                }),
                'last_sync' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Mai',
                'can_sync' => $this->canSync()
            ]);
        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore lettura locale: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle zone di consegna: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync delivery zones from Oppla city_areas to local database
     *
     * OPPLA Architecture:
     * - city_areas = geographic zones (Livorno Centro, Pisa Nord, etc.)
     * - delivery_zones = pricing tiers for each area (0-3km = €3, etc.)
     * - restaurants belong to city_areas
     */
    public function sync()
    {
        try {
            // Controlla timeout di 1 ora
            if (!$this->canSync()) {
                $lastSync = Cache::get('delivery_zones_last_sync');
                $nextSync = Carbon::parse($lastSync)->addHour()->diffForHumans();

                return response()->json([
                    'success' => false,
                    'message' => "Sincronizzazione disponibile {$nextSync}. Attendere per evitare sovraccarico.",
                    'can_sync' => false,
                    'last_sync' => Carbon::parse($lastSync)->diffForHumans()
                ], 429);
            }

            Log::info('[DeliveryZones] Inizio sincronizzazione city_areas da Oppla via API/scraping');

            // Use OpplaIntegrationService instead of direct DB access
            $opplaCityAreas = $this->opplaService->getCityAreas();

            $syncedCount = 0;

            // Disabilita tutte le zone sincronizzate da oppla
            DeliveryZone::whereNotNull('oppla_id')->update(['is_active' => false]);

            foreach ($opplaCityAreas as $area) {
                // Adapt field names based on API response structure
                $areaName = $area['name'] ?? $area['area_name'] ?? null;
                $cityName = $area['city']['name'] ?? $area['city_name'] ?? null;
                $opplaId = $area['id'] ?? $area['external_id'] ?? null;
                $logisticPartner = $area['logistic_partner']['name'] ?? $area['logistic_partner'] ?? null;

                if (!$areaName || !$cityName || !$opplaId) {
                    Log::warning('[DeliveryZones] Skipping invalid city area', ['data' => $area]);
                    continue;
                }

                // Descrizione: indica il partner logistico se presente
                $description = $logisticPartner
                    ? "Gestita da {$logisticPartner}"
                    : "Consegne OPPLA";

                // Crea o aggiorna la zona nel database locale
                DeliveryZone::updateOrCreate(
                    [
                        'oppla_id' => $opplaId
                    ],
                    [
                        'name' => $areaName,     // "Livorno Centro"
                        'city' => $cityName,    // "Livorno"
                        'description' => $description,
                        'postal_codes' => [],
                        'price_ranges' => [],
                        'source' => 'oppla_sync',
                        'is_active' => true
                    ]
                );

                $syncedCount++;
            }

            // Salva il timestamp della sincronizzazione
            Cache::put('delivery_zones_last_sync', now(), 3600);

            Log::info('[DeliveryZones] Sincronizzazione completata via API/scraping', ['zones' => $syncedCount]);

            return response()->json([
                'success' => true,
                'message' => "Sincronizzazione completata: {$syncedCount} zone di consegna (city areas) aggiornate via API",
                'synced' => $syncedCount,
                'last_sync' => 'Ora',
                'can_sync' => false
            ]);
        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore sincronizzazione: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if sync is allowed (1 hour timeout)
     */
    private function canSync(): bool
    {
        $lastSync = Cache::get('delivery_zones_last_sync');
        
        if (!$lastSync) {
            return true;
        }

        return Carbon::parse($lastSync)->addHour()->isPast();
    }

    /**
     * Get all zones with geometry for map display
     */
    public function mapZones()
    {
        try {
            $zones = DeliveryZone::where('is_active', true)
                ->orderBy('city')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $zones->map(function($zone) {
                    return [
                        'id' => $zone->id,
                        'oppla_id' => $zone->oppla_id,
                        'name' => $zone->name,
                        'city' => $zone->city,
                        'description' => $zone->description,
                        'geometry' => $zone->geometry,
                        'center_lat' => $zone->center_lat,
                        'center_lng' => $zone->center_lng,
                        'color' => $zone->color ?? '#3b82f6',
                        'source' => $zone->source ?? 'manual',
                        'has_geometry' => $zone->hasGeometry(),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore lettura zone mappa: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle zone: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a single delivery zone
     */
    public function show($id)
    {
        try {
            $zone = DeliveryZone::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $zone->id,
                    'oppla_id' => $zone->oppla_id,
                    'name' => $zone->name,
                    'city' => $zone->city,
                    'description' => $zone->description,
                    'postal_codes' => $zone->postal_codes ?? [],
                    'price_ranges' => $zone->price_ranges ?? [],
                    'geometry' => $zone->geometry,
                    'center_lat' => $zone->center_lat,
                    'center_lng' => $zone->center_lng,
                    'color' => $zone->color ?? '#3b82f6',
                    'source' => $zone->source ?? 'manual',
                    'is_active' => $zone->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Zona non trovata'
            ], 404);
        }
    }

    /**
     * Create a new delivery zone
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'description' => 'nullable|string',
            'postal_codes' => 'nullable|array',
            'price_ranges' => 'nullable|array',
            'geometry' => 'nullable|array',
            'geometry.type' => 'nullable|string|in:Polygon,MultiPolygon',
            'geometry.coordinates' => 'nullable|array',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lng' => 'nullable|numeric|between:-180,180',
            'color' => 'nullable|string|max:7',
        ]);

        try {
            $zone = new DeliveryZone();
            $zone->name = $validated['name'];
            $zone->city = $validated['city'];
            $zone->description = $validated['description'] ?? null;
            $zone->postal_codes = $validated['postal_codes'] ?? [];
            $zone->price_ranges = $validated['price_ranges'] ?? [];
            $zone->geometry = $validated['geometry'] ?? null;
            $zone->color = $validated['color'] ?? '#3b82f6';
            $zone->source = 'manual';
            $zone->is_active = true;

            // Calculate center from geometry if not provided
            if (!empty($validated['geometry']) && empty($validated['center_lat'])) {
                $center = $zone->calculateCenter();
                $zone->center_lat = $center['lat'];
                $zone->center_lng = $center['lng'];
            } else {
                $zone->center_lat = $validated['center_lat'] ?? null;
                $zone->center_lng = $validated['center_lng'] ?? null;
            }

            $zone->save();

            Log::info('[DeliveryZones] Zona creata manualmente', [
                'id' => $zone->id,
                'name' => $zone->name,
                'city' => $zone->city,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zona di consegna creata con successo',
                'data' => $zone,
            ], 201);
        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore creazione zona: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione della zona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing delivery zone
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'postal_codes' => 'nullable|array',
            'price_ranges' => 'nullable|array',
            'geometry' => 'nullable|array',
            'geometry.type' => 'nullable|string|in:Polygon,MultiPolygon',
            'geometry.coordinates' => 'nullable|array',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lng' => 'nullable|numeric|between:-180,180',
            'color' => 'nullable|string|max:7',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $zone = DeliveryZone::findOrFail($id);

            // Update fields
            if (isset($validated['name'])) $zone->name = $validated['name'];
            if (isset($validated['city'])) $zone->city = $validated['city'];
            if (array_key_exists('description', $validated)) $zone->description = $validated['description'];
            if (isset($validated['postal_codes'])) $zone->postal_codes = $validated['postal_codes'];
            if (isset($validated['price_ranges'])) $zone->price_ranges = $validated['price_ranges'];
            if (isset($validated['color'])) $zone->color = $validated['color'];
            if (isset($validated['is_active'])) $zone->is_active = $validated['is_active'];

            // Update geometry
            if (array_key_exists('geometry', $validated)) {
                $zone->geometry = $validated['geometry'];
                
                // Recalculate center if geometry changed
                if ($zone->hasGeometry() && empty($validated['center_lat'])) {
                    $center = $zone->calculateCenter();
                    $zone->center_lat = $center['lat'];
                    $zone->center_lng = $center['lng'];
                }
            }

            if (isset($validated['center_lat'])) $zone->center_lat = $validated['center_lat'];
            if (isset($validated['center_lng'])) $zone->center_lng = $validated['center_lng'];

            $zone->save();

            Log::info('[DeliveryZones] Zona aggiornata', [
                'id' => $zone->id,
                'name' => $zone->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zona di consegna aggiornata con successo',
                'data' => $zone,
            ]);
        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore aggiornamento zona: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento della zona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a delivery zone
     */
    public function destroy($id)
    {
        try {
            $zone = DeliveryZone::findOrFail($id);

            // Don't allow deleting Oppla-synced zones
            if ($zone->oppla_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non è possibile eliminare zone sincronizzate da Oppla. Puoi solo disattivarle.'
                ], 403);
            }

            $zoneName = $zone->name;
            $zone->delete();

            Log::info('[DeliveryZones] Zona eliminata', [
                'id' => $id,
                'name' => $zoneName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Zona di consegna eliminata con successo',
            ]);
        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore eliminazione zona: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione della zona: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Push local zones to OPPLA (create city_areas via API/scraping)
     */
    public function pushToOppla(Request $request)
    {
        $validated = $request->validate([
            'zone_ids' => 'required|array',
            'zone_ids.*' => 'exists:delivery_zones,id',
        ]);

        try {
            $pushedCount = 0;
            $errors = [];

            foreach ($validated['zone_ids'] as $zoneId) {
                $zone = DeliveryZone::findOrFail($zoneId);

                // Skip if already synced to OPPLA
                if ($zone->oppla_id) {
                    $errors[] = "Zona '{$zone->name}' già sincronizzata su OPPLA (ID: {$zone->oppla_id})";
                    continue;
                }

                try {
                    // Use OpplaIntegrationService instead of direct DB access
                    $result = $this->opplaService->createCityArea([
                        'name' => $zone->name,
                        'city_name' => $zone->city,
                        'slug' => \Illuminate\Support\Str::slug($zone->name . '-' . $zone->city),
                        // city_id will be resolved by the service
                    ]);

                    if ($result && isset($result['id'])) {
                        // Update local zone with OPPLA ID
                        $zone->update([
                            'oppla_id' => $result['id'],
                            'source' => 'manual_synced',
                        ]);

                        $pushedCount++;

                        Log::info('[DeliveryZones] Zona sincronizzata verso OPPLA via API', [
                            'local_id' => $zone->id,
                            'oppla_id' => $result['id'],
                            'name' => $zone->name,
                            'city' => $zone->city,
                        ]);
                    } else {
                        throw new \Exception('API returned no ID for created city area');
                    }

                } catch (\Exception $e) {
                    $errors[] = "Zona '{$zone->name}': {$e->getMessage()}";
                    Log::error('[DeliveryZones] Errore sync verso OPPLA', [
                        'zone_id' => $zone->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sincronizzazione completata: {$pushedCount} zone inviate a OPPLA via API",
                'data' => [
                    'pushed' => $pushedCount,
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore push OPPLA: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Debug endpoint - DEPRECATED
     *
     * This method uses direct database access which we're moving away from.
     * Use the sync endpoint with detailed logging instead.
     * Check backend/storage/logs/laravel.log for sync details.
     */
    /* DEPRECATED - COMMENTED OUT
    public function debugOppla()
    {
        try {
            // Query le city_areas dal database OPPLA
            $cityAreas = DB::connection('oppla')
                ->table('city_areas as ca')
                ->join('cities as c', 'ca.city_id', '=', 'c.id')
                ->leftJoin('logistic_partners as lp', 'ca.logistic_partner_id', '=', 'lp.id')
                ->select(
                    'ca.id as oppla_id',
                    'ca.name as area_name',
                    'ca.slug',
                    'c.name as city_name',
                    'lp.name as logistic_partner'
                )
                ->orderBy('c.name')
                ->orderBy('ca.name')
                ->limit(100)
                ->get();

            // Verifica se ci sono nomi che sembrano ristoranti
            $restaurantKeywords = ['pizzeria', 'ristorante', 'trattoria', 'bar', 'caffè', 'osteria', 'taverna', 'sushi', 'kebab', 'poke'];

            $suspiciousNames = $cityAreas->filter(function($area) use ($restaurantKeywords) {
                $name = strtolower($area->area_name);

                foreach ($restaurantKeywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        return true;
                    }
                }
                return false;
            });

            // Zone locali
            $localZones = DeliveryZone::where('is_active', true)
                ->orderBy('city')
                ->orderBy('name')
                ->get(['id', 'name', 'city', 'source', 'oppla_id']);

            return response()->json([
                'success' => true,
                'data' => [
                    'oppla_city_areas' => [
                        'total' => $cityAreas->count(),
                        'suspicious_count' => $suspiciousNames->count(),
                        'data' => $cityAreas->map(function($area) {
                            return [
                                'oppla_id' => $area->oppla_id,
                                'name' => $area->area_name,
                                'city' => $area->city_name,
                                'logistic_partner' => $area->logistic_partner,
                                'slug' => $area->slug,
                            ];
                        }),
                        'suspicious_names' => $suspiciousNames->map(function($area) {
                            return [
                                'oppla_id' => $area->oppla_id,
                                'name' => $area->area_name,
                                'city' => $area->city_name,
                                'warning' => 'Questo nome sembra essere un ristorante invece di una zona geografica',
                            ];
                        })->values(),
                    ],
                    'local_delivery_zones' => [
                        'total' => $localZones->count(),
                        'data' => $localZones,
                    ],
                ],
                'analysis' => [
                    'has_restaurant_names' => $suspiciousNames->count() > 0,
                    'message' => $suspiciousNames->count() > 0
                        ? "⚠️ Trovati {$suspiciousNames->count()} nomi sospetti che sembrano ristoranti invece di zone geografiche"
                        : "✅ Nessun nome sospetto trovato. Le city_areas sembrano essere zone geografiche corrette.",
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore debug OPPLA: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'analisi: ' . $e->getMessage(),
                'error_details' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }
    */

    /**
     * Clean up delivery zones with restaurant names
     * Removes zones that look like restaurant names instead of geographic zones
     */
    public function cleanupRestaurantNames(Request $request)
    {
        try {
            $dryRun = $request->get('dry_run', true);

            // Get all zones
            $zones = DeliveryZone::all();
            $suspicious = [];
            $cleaned = 0;

            // Restaurant keywords for detection
            $restaurantKeywords = [
                'pizzeria', 'ristorante', 'trattoria', 'osteria', 'bar',
                'cafè', 'cafe', 'bistrot', 'bistro', 'pub', 'paninoteca',
                'hamburgeria', 'gelateria', 'pasticceria', 'braceria',
                'steakhouse', 'sushi', 'poke', 'kebab', 'street food',
                'da ', 'al ', 'la ', 'il ', 'lo ', "l'",
            ];

            foreach ($zones as $zone) {
                $nameLower = mb_strtolower($zone->name);
                $isSuspicious = false;

                // Check for restaurant keywords
                foreach ($restaurantKeywords as $keyword) {
                    if (str_contains($nameLower, $keyword)) {
                        $isSuspicious = true;
                        break;
                    }
                }

                // Additional patterns
                if (!$isSuspicious) {
                    // Check for apostrophe patterns (L'Approdo, D'Amore)
                    if (preg_match("/[ld]'[a-z]/i", $zone->name)) {
                        $isSuspicious = true;
                    }
                    // Check for & symbol
                    if (str_contains($zone->name, '&')) {
                        $isSuspicious = true;
                    }
                    // Check for numbers followed by words
                    if (preg_match('/^\d+\s+[a-z]/i', $zone->name)) {
                        $isSuspicious = true;
                    }
                    // Long names (> 3 words) without geographic indicators
                    if (str_word_count($zone->name) > 3) {
                        $hasGeoIndicator = preg_match('/(centro|nord|sud|est|ovest|zona|area)/i', $zone->name);
                        if (!$hasGeoIndicator) {
                            $isSuspicious = true;
                        }
                    }
                }

                if ($isSuspicious) {
                    $suspicious[] = [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city,
                        'oppla_id' => $zone->oppla_id,
                    ];

                    if (!$dryRun) {
                        $zone->delete();
                        $cleaned++;
                    }
                }
            }

            $suspiciousCount = count($suspicious);
            return response()->json([
                'success' => true,
                'message' => $dryRun
                    ? "Trovate {$suspiciousCount} zone con nomi sospetti (simulazione - nessuna eliminazione)"
                    : "Eliminate {$cleaned} zone con nomi di ristoranti",
                'dry_run' => $dryRun,
                'data' => [
                    'total_zones' => $zones->count(),
                    'suspicious_count' => count($suspicious),
                    'cleaned_count' => $cleaned,
                    'suspicious_zones' => $suspicious,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('[DeliveryZones] Errore cleanup: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la pulizia: ' . $e->getMessage(),
            ], 500);
        }
    }
}
