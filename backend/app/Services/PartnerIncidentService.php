<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\PartnerIncident;
use App\Models\PartnerPenalty;
use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerIncidentService
{
    /**
     * Segnala un ritardo nella consegna al rider
     */
    public function reportDelay(
        int $restaurantId,
        int $deliveryId,
        int $delayMinutes,
        ?int $reportedByUserId = null,
        ?string $description = null
    ): PartnerIncident {
        return DB::transaction(function () use ($restaurantId, $deliveryId, $delayMinutes, $reportedByUserId, $description) {
            $delivery = Delivery::findOrFail($deliveryId);

            $incident = PartnerIncident::create([
                'restaurant_id' => $restaurantId,
                'delivery_id' => $deliveryId,
                'rider_fleet_id' => $delivery->rider_fleet_id ?? null,
                'reported_by_user_id' => $reportedByUserId,
                'incident_type' => PartnerIncident::TYPE_DELAY,
                'delay_minutes' => $delayMinutes,
                'description' => $description,
                'status' => PartnerIncident::STATUS_PENDING,
            ]);

            // Aggiorna delivery con il ritardo
            $delivery->update(['pickup_delay_minutes' => $delayMinutes]);

            // Verifica soglia ritardi
            $this->checkDelayThreshold($restaurantId);

            // Aggiorna contatori
            $this->updateRestaurantCounters($restaurantId);

            Log::info("PartnerIncident: Ritardo segnalato", [
                'restaurant_id' => $restaurantId,
                'delivery_id' => $deliveryId,
                'delay_minutes' => $delayMinutes,
            ]);

            return $incident;
        });
    }

    /**
     * Segnala prodotto dimenticato dal ristorante
     */
    public function reportForgottenItem(
        int $restaurantId,
        int $deliveryId,
        ?int $reportedByUserId = null,
        ?string $description = null
    ): array {
        return DB::transaction(function () use ($restaurantId, $deliveryId, $reportedByUserId, $description) {
            $restaurant = Restaurant::findOrFail($restaurantId);
            $delivery = Delivery::findOrFail($deliveryId);
            $settings = PartnerProtectionSettings::getEffectiveSettings($restaurantId);

            // Crea incidente
            $incident = PartnerIncident::create([
                'restaurant_id' => $restaurantId,
                'delivery_id' => $deliveryId,
                'rider_fleet_id' => $delivery->rider_fleet_id ?? null,
                'reported_by_user_id' => $reportedByUserId,
                'incident_type' => PartnerIncident::TYPE_FORGOTTEN_ITEM,
                'description' => $description,
                'status' => PartnerIncident::STATUS_PENDING,
            ]);

            // Crea penale
            $penalty = PartnerPenalty::create([
                'restaurant_id' => $restaurantId,
                'client_id' => $restaurant->client_id,
                'penalty_type' => PartnerPenalty::TYPE_FORGOTTEN_ITEM,
                'amount' => $settings['forgotten_item_penalty'],
                'description' => "Penale per prodotto dimenticato - Consegna #{$delivery->order_id}",
                'incident_ids' => [$incident->id],
            ]);

            $incident->update(['penalty_id' => $penalty->id]);

            // Crea consegna di ritorno se configurato
            $returnDelivery = null;
            if ($settings['forgotten_item_double_delivery']) {
                $returnDelivery = $this->createReturnDelivery($delivery, $settings);
            }

            $this->updateRestaurantCounters($restaurantId);

            Log::info("PartnerIncident: Prodotto dimenticato segnalato", [
                'restaurant_id' => $restaurantId,
                'delivery_id' => $deliveryId,
                'penalty_amount' => $penalty->amount,
                'return_delivery_created' => $returnDelivery !== null,
            ]);

            return [
                'incident' => $incident,
                'penalty' => $penalty,
                'return_delivery' => $returnDelivery,
            ];
        });
    }

    /**
     * Segnala ordine voluminoso non dichiarato
     */
    public function reportBulkyUnmarked(
        int $restaurantId,
        int $deliveryId,
        ?int $reportedByUserId = null,
        ?string $description = null
    ): array {
        return DB::transaction(function () use ($restaurantId, $deliveryId, $reportedByUserId, $description) {
            $restaurant = Restaurant::findOrFail($restaurantId);
            $delivery = Delivery::findOrFail($deliveryId);
            $settings = PartnerProtectionSettings::getEffectiveSettings($restaurantId);

            // Crea incidente
            $incident = PartnerIncident::create([
                'restaurant_id' => $restaurantId,
                'delivery_id' => $deliveryId,
                'rider_fleet_id' => $delivery->rider_fleet_id ?? null,
                'reported_by_user_id' => $reportedByUserId,
                'incident_type' => PartnerIncident::TYPE_BULKY_UNMARKED,
                'description' => $description,
                'status' => PartnerIncident::STATUS_PENDING,
            ]);

            // Aggiorna delivery
            $delivery->update([
                'is_bulky' => true,
                'bulky_confirmed_by_rider' => true,
                'bulky_reported_by_restaurant' => false,
            ]);

            // Verifica se siamo oltre la soglia
            $bulkyCount = $this->getBulkyUnmarkedCount($restaurantId);
            $isPenaltyRepeated = $bulkyCount >= $settings['bulky_unmarked_threshold'];

            // Crea penale
            $penaltyType = $isPenaltyRepeated
                ? PartnerPenalty::TYPE_BULKY_REPEATED
                : PartnerPenalty::TYPE_BULKY_UNMARKED;

            $penaltyAmount = $isPenaltyRepeated
                ? $settings['bulky_repeated_penalty']
                : $settings['bulky_unmarked_penalty'];

            $penalty = PartnerPenalty::create([
                'restaurant_id' => $restaurantId,
                'client_id' => $restaurant->client_id,
                'penalty_type' => $penaltyType,
                'amount' => $penaltyAmount,
                'description' => $isPenaltyRepeated
                    ? "Penale per ordini voluminosi non segnalati ripetuti ({$bulkyCount} volte)"
                    : "Penale per ordine voluminoso non segnalato - Consegna #{$delivery->order_id}",
                'incident_ids' => [$incident->id],
            ]);

            $incident->update(['penalty_id' => $penalty->id]);

            $this->updateRestaurantCounters($restaurantId);

            Log::info("PartnerIncident: Voluminoso non segnalato", [
                'restaurant_id' => $restaurantId,
                'delivery_id' => $deliveryId,
                'bulky_count' => $bulkyCount,
                'is_repeated' => $isPenaltyRepeated,
                'penalty_amount' => $penalty->amount,
            ]);

            return [
                'incident' => $incident,
                'penalty' => $penalty,
                'is_repeated_violation' => $isPenaltyRepeated,
                'violation_count' => $bulkyCount,
            ];
        });
    }

    /**
     * Verifica soglia ritardi e crea penale se superata
     */
    public function checkDelayThreshold(int $restaurantId): ?PartnerPenalty
    {
        $restaurant = Restaurant::findOrFail($restaurantId);
        $settings = PartnerProtectionSettings::getEffectiveSettings($restaurantId);

        $periodStart = Carbon::now()->subDays($settings['delay_threshold_period_days']);

        $delayCount = PartnerIncident::where('restaurant_id', $restaurantId)
            ->where('incident_type', PartnerIncident::TYPE_DELAY)
            ->where('created_at', '>=', $periodStart)
            ->count();

        if ($delayCount >= $settings['delay_threshold_count']) {
            // Verifica se esiste già una penale per questo periodo
            $existingPenalty = PartnerPenalty::where('restaurant_id', $restaurantId)
                ->where('penalty_type', PartnerPenalty::TYPE_DELAY_THRESHOLD)
                ->where('period_start', '>=', $periodStart)
                ->first();

            if (!$existingPenalty) {
                $incidentIds = PartnerIncident::where('restaurant_id', $restaurantId)
                    ->where('incident_type', PartnerIncident::TYPE_DELAY)
                    ->where('created_at', '>=', $periodStart)
                    ->pluck('id')
                    ->toArray();

                $penalty = PartnerPenalty::create([
                    'restaurant_id' => $restaurantId,
                    'client_id' => $restaurant->client_id,
                    'penalty_type' => PartnerPenalty::TYPE_DELAY_THRESHOLD,
                    'amount' => $settings['delay_penalty_amount'],
                    'description' => "Superamento soglia ritardi: {$delayCount} ritardi in {$settings['delay_threshold_period_days']} giorni",
                    'incident_ids' => $incidentIds,
                    'period_start' => $periodStart,
                    'period_end' => Carbon::now(),
                ]);

                Log::warning("PartnerIncident: Soglia ritardi superata", [
                    'restaurant_id' => $restaurantId,
                    'delay_count' => $delayCount,
                    'threshold' => $settings['delay_threshold_count'],
                    'penalty_amount' => $penalty->amount,
                ]);

                return $penalty;
            }
        }

        return null;
    }

    /**
     * Risolve un incidente
     */
    public function resolveIncident(
        int $incidentId,
        int $resolvedByUserId,
        ?string $resolutionNotes = null,
        bool $waivePenalty = false
    ): PartnerIncident {
        return DB::transaction(function () use ($incidentId, $resolvedByUserId, $resolutionNotes, $waivePenalty) {
            $incident = PartnerIncident::findOrFail($incidentId);

            $incident->update([
                'status' => PartnerIncident::STATUS_RESOLVED,
                'resolved_by_user_id' => $resolvedByUserId,
                'resolved_at' => Carbon::now(),
                'resolution_notes' => $resolutionNotes,
            ]);

            // Annulla penale se richiesto
            if ($waivePenalty && $incident->penalty_id) {
                $incident->penalty->waive();
            }

            $this->updateRestaurantCounters($incident->restaurant_id);

            return $incident->fresh();
        });
    }

    /**
     * Crea consegna di ritorno per prodotto dimenticato
     */
    protected function createReturnDelivery(Delivery $originalDelivery, array $settings): Delivery
    {
        $fee = $originalDelivery->delivery_fee_total * $settings['double_delivery_multiplier'];

        return Delivery::create([
            'client_id' => $originalDelivery->client_id,
            'order_id' => $originalDelivery->order_id . '-RETURN',
            'order_type' => $originalDelivery->order_type,
            'is_partner_logistico' => $originalDelivery->is_partner_logistico,
            'pickup_address' => $originalDelivery->pickup_address,
            'delivery_address' => $originalDelivery->delivery_address,
            'distance_km' => $originalDelivery->distance_km,
            'order_amount' => 0,
            'delivery_fee_base' => $fee,
            'delivery_fee_total' => $fee,
            'order_date' => Carbon::now(),
            'status' => 'pending',
            'is_return_trip' => true,
            'original_delivery_id' => $originalDelivery->id,
            'is_double_delivery' => true,
            'double_delivery_reason' => 'forgotten_item',
            'note' => "Consegna di ritorno per prodotti dimenticati - Originale: #{$originalDelivery->order_id}",
        ]);
    }

    /**
     * Conta ordini voluminosi non segnalati nel periodo
     */
    protected function getBulkyUnmarkedCount(int $restaurantId): int
    {
        $settings = PartnerProtectionSettings::getEffectiveSettings($restaurantId);
        $periodStart = Carbon::now()->subDays($settings['bulky_unmarked_period_days']);

        return PartnerIncident::where('restaurant_id', $restaurantId)
            ->where('incident_type', PartnerIncident::TYPE_BULKY_UNMARKED)
            ->where('created_at', '>=', $periodStart)
            ->count();
    }

    /**
     * Aggiorna i contatori cache del ristorante
     */
    public function updateRestaurantCounters(int $restaurantId): void
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $incidentCount = PartnerIncident::where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $delayCount = PartnerIncident::where('restaurant_id', $restaurantId)
            ->where('incident_type', PartnerIncident::TYPE_DELAY)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $bulkyCount = PartnerIncident::where('restaurant_id', $restaurantId)
            ->where('incident_type', PartnerIncident::TYPE_BULKY_UNMARKED)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        Restaurant::where('id', $restaurantId)->update([
            'incident_count_30d' => $incidentCount,
            'delay_count_30d' => $delayCount,
            'bulky_unmarked_count_30d' => $bulkyCount,
        ]);
    }

    /**
     * Ottiene statistiche incidenti per un ristorante
     */
    public function getRestaurantStats(int $restaurantId, ?int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $incidents = PartnerIncident::where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $byType = $incidents->groupBy('incident_type')->map->count();
        $byStatus = $incidents->groupBy('status')->map->count();

        $penalties = PartnerPenalty::where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalPenalties = $penalties->sum('amount');
        $pendingPenalties = $penalties->where('billing_status', PartnerPenalty::STATUS_PENDING)->sum('amount');

        return [
            'period_days' => $days,
            'total_incidents' => $incidents->count(),
            'incidents_by_type' => $byType->toArray(),
            'incidents_by_status' => $byStatus->toArray(),
            'total_penalties' => $totalPenalties,
            'pending_penalties' => $pendingPenalties,
            'penalties_count' => $penalties->count(),
        ];
    }
}
