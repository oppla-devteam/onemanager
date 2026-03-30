<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PartnerIncident;
use App\Models\PartnerPenalty;
use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use App\Models\RestaurantTimeSlot;
use App\Models\RestaurantDeliveryZone;
use App\Services\PartnerIncidentService;
use App\Services\PartnerValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PartnerProtectionController extends Controller
{
    protected PartnerIncidentService $incidentService;
    protected PartnerValidationService $validationService;

    public function __construct(
        PartnerIncidentService $incidentService,
        PartnerValidationService $validationService
    ) {
        $this->incidentService = $incidentService;
        $this->validationService = $validationService;
    }

    // ==================== INCIDENTS ====================

    /**
     * Lista incidenti con filtri
     */
    public function listIncidents(Request $request): JsonResponse
    {
        $query = PartnerIncident::with(['restaurant', 'delivery', 'reportedBy', 'penalty']);

        // Filtri
        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->has('incident_type')) {
            $query->where('incident_type', $request->incident_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $incidents = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $incidents,
        ]);
    }

    /**
     * Statistiche incidenti
     */
    public function incidentStats(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $startDate = Carbon::now()->subDays($days);

        $stats = [
            'total' => PartnerIncident::where('created_at', '>=', $startDate)->count(),
            'pending' => PartnerIncident::where('created_at', '>=', $startDate)->pending()->count(),
            'by_type' => PartnerIncident::where('created_at', '>=', $startDate)
                ->selectRaw('incident_type, COUNT(*) as count')
                ->groupBy('incident_type')
                ->pluck('count', 'incident_type'),
            'by_status' => PartnerIncident::where('created_at', '>=', $startDate)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ];

        if ($request->has('restaurant_id')) {
            $stats['restaurant_stats'] = $this->incidentService->getRestaurantStats(
                $request->restaurant_id,
                $days
            );
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Segnala ritardo
     */
    public function reportDelay(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'delivery_id' => 'required|exists:deliveries,id',
            'delay_minutes' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $incident = $this->incidentService->reportDelay(
                $request->restaurant_id,
                $request->delivery_id,
                $request->delay_minutes,
                auth()->id(),
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Ritardo segnalato con successo',
                'data' => $incident->load(['restaurant', 'delivery']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Segnala prodotto dimenticato
     */
    public function reportForgottenItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'delivery_id' => 'required|exists:deliveries,id',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->incidentService->reportForgottenItem(
                $request->restaurant_id,
                $request->delivery_id,
                auth()->id(),
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Prodotto dimenticato segnalato con successo',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Segnala ordine voluminoso non segnalato
     */
    public function reportBulkyUnmarked(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'delivery_id' => 'required|exists:deliveries,id',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->incidentService->reportBulkyUnmarked(
                $request->restaurant_id,
                $request->delivery_id,
                auth()->id(),
                $request->description
            );

            return response()->json([
                'success' => true,
                'message' => 'Ordine voluminoso non segnalato registrato',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Risolvi incidente
     */
    public function resolveIncident(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'nullable|string|max:2000',
            'waive_penalty' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $incident = $this->incidentService->resolveIncident(
                $id,
                auth()->id(),
                $request->resolution_notes,
                $request->boolean('waive_penalty', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'Incidente risolto con successo',
                'data' => $incident->load(['restaurant', 'penalty']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== PENALTIES ====================

    /**
     * Lista penali con filtri
     */
    public function listPenalties(Request $request): JsonResponse
    {
        $query = PartnerPenalty::with(['restaurant', 'client', 'invoice']);

        if ($request->has('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->has('billing_status')) {
            $query->where('billing_status', $request->billing_status);
        }

        if ($request->has('penalty_type')) {
            $query->where('penalty_type', $request->penalty_type);
        }

        $penalties = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $penalties,
        ]);
    }

    /**
     * Preview fatture penali
     */
    public function previewPenaltyInvoices(Request $request): JsonResponse
    {
        $pendingPenalties = PartnerPenalty::pending()
            ->with(['restaurant', 'client'])
            ->get()
            ->groupBy('client_id');

        $preview = [];
        foreach ($pendingPenalties as $clientId => $penalties) {
            $client = $penalties->first()->client;
            $preview[] = [
                'client_id' => $clientId,
                'client_name' => $client?->ragione_sociale ?? 'Cliente #' . $clientId,
                'penalties_count' => $penalties->count(),
                'total_amount' => $penalties->sum('amount'),
                'penalties' => $penalties->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'type' => $p->penalty_type,
                        'type_label' => PartnerPenalty::getTypeLabel($p->penalty_type),
                        'amount' => $p->amount,
                        'restaurant_name' => $p->restaurant?->nome,
                        'created_at' => $p->created_at->format('d/m/Y'),
                    ];
                })->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $preview,
            'total_pending' => PartnerPenalty::pending()->sum('amount'),
        ]);
    }

    /**
     * Annulla penale
     */
    public function waivePenalty(int $id): JsonResponse
    {
        try {
            $penalty = PartnerPenalty::findOrFail($id);

            if (!$penalty->isPending()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solo le penali in attesa possono essere annullate',
                ], 422);
            }

            $penalty->waive();

            return response()->json([
                'success' => true,
                'message' => 'Penale annullata',
                'data' => $penalty->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== SETTINGS ====================

    /**
     * Leggi impostazioni
     */
    public function getSettings(?int $restaurantId = null): JsonResponse
    {
        $settings = PartnerProtectionSettings::getForRestaurant($restaurantId);
        $effective = PartnerProtectionSettings::getEffectiveSettings($restaurantId);

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'effective' => $effective,
                'is_global' => $settings->restaurant_id === null,
            ],
        ]);
    }

    /**
     * Aggiorna impostazioni
     */
    public function updateSettings(Request $request, ?int $restaurantId = null): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'delay_threshold_count' => 'integer|min:1',
            'delay_threshold_period_days' => 'integer|min:1|max:365',
            'delay_penalty_amount' => 'numeric|min:0',
            'forgotten_item_penalty' => 'numeric|min:0',
            'forgotten_item_double_delivery' => 'boolean',
            'bulky_surcharge' => 'numeric|min:0',
            'bulky_unmarked_penalty' => 'numeric|min:0',
            'bulky_unmarked_threshold' => 'integer|min:1',
            'bulky_unmarked_period_days' => 'integer|min:1|max:365',
            'bulky_repeated_penalty' => 'numeric|min:0',
            'double_delivery_multiplier' => 'numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = PartnerProtectionSettings::updateOrCreate(
            ['restaurant_id' => $restaurantId],
            $request->only([
                'delay_threshold_count',
                'delay_threshold_period_days',
                'delay_penalty_amount',
                'forgotten_item_penalty',
                'forgotten_item_double_delivery',
                'bulky_surcharge',
                'bulky_unmarked_penalty',
                'bulky_unmarked_threshold',
                'bulky_unmarked_period_days',
                'bulky_repeated_penalty',
                'double_delivery_multiplier',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Impostazioni aggiornate',
            'data' => $settings,
        ]);
    }

    // ==================== TIME SLOTS ====================

    /**
     * Ottieni fasce orarie ristorante
     */
    public function getTimeSlots(int $restaurantId): JsonResponse
    {
        $schedule = $this->validationService->getRestaurantSchedule($restaurantId);
        $overrides = $this->validationService->getUpcomingOverrides($restaurantId);

        return response()->json([
            'success' => true,
            'data' => [
                'schedule' => $schedule,
                'upcoming_overrides' => $overrides,
            ],
        ]);
    }

    /**
     * Aggiorna fasce orarie
     */
    public function updateTimeSlots(Request $request, int $restaurantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slots' => 'required|array',
            'slots.*.day_of_week' => 'nullable|integer|min:0|max:6',
            'slots.*.slot_type' => 'required|in:lunch,dinner,all_day,custom',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i',
            'slots.*.is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Rimuovi vecchi slot senza override
        RestaurantTimeSlot::where('restaurant_id', $restaurantId)
            ->whereNull('override_date')
            ->delete();

        // Crea nuovi slot
        foreach ($request->slots as $slotData) {
            RestaurantTimeSlot::create([
                'restaurant_id' => $restaurantId,
                'day_of_week' => $slotData['day_of_week'] ?? null,
                'slot_type' => $slotData['slot_type'],
                'start_time' => $slotData['start_time'],
                'end_time' => $slotData['end_time'],
                'is_active' => $slotData['is_active'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fasce orarie aggiornate',
        ]);
    }

    /**
     * Aggiungi chiusura straordinaria
     */
    public function addClosureOverride(Request $request, int $restaurantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'is_closed' => 'boolean',
            'start_time' => 'required_if:is_closed,false|date_format:H:i',
            'end_time' => 'required_if:is_closed,false|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $override = RestaurantTimeSlot::updateOrCreate(
            [
                'restaurant_id' => $restaurantId,
                'override_date' => $request->date,
            ],
            [
                'is_closed_override' => $request->boolean('is_closed', true),
                'slot_type' => 'custom',
                'start_time' => $request->is_closed ? '00:00' : $request->start_time,
                'end_time' => $request->is_closed ? '00:00' : $request->end_time,
                'is_active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $request->boolean('is_closed') ? 'Chiusura straordinaria aggiunta' : 'Orario straordinario aggiunto',
            'data' => $override,
        ]);
    }

    // ==================== DELIVERY ZONES ====================

    /**
     * Ottieni zone di consegna ristorante
     */
    public function getDeliveryZones(int $restaurantId): JsonResponse
    {
        $zones = $this->validationService->getAvailableDeliveryZones($restaurantId);

        return response()->json([
            'success' => true,
            'data' => $zones,
        ]);
    }

    /**
     * Aggiorna zone di consegna
     */
    public function updateDeliveryZones(Request $request, int $restaurantId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'zones' => 'required|array',
            'zones.*.delivery_zone_id' => 'required|exists:delivery_zones,id',
            'zones.*.is_active' => 'boolean',
            'zones.*.surcharge' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->zones as $zoneData) {
            RestaurantDeliveryZone::updateOrCreate(
                [
                    'restaurant_id' => $restaurantId,
                    'delivery_zone_id' => $zoneData['delivery_zone_id'],
                ],
                [
                    'is_active' => $zoneData['is_active'] ?? true,
                    'surcharge' => $zoneData['surcharge'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Zone di consegna aggiornate',
        ]);
    }

    // ==================== VALIDATION ====================

    /**
     * Valida ordine
     */
    public function validateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'requested_time' => 'nullable|date',
            'is_bulky' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->validationService->validateOrder(
            $request->restaurant_id,
            $request->delivery_zone_id,
            $request->requested_time ? Carbon::parse($request->requested_time) : null,
            $request->boolean('is_bulky', false)
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
