<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\RestaurantTimeSlot;
use App\Models\RestaurantDeliveryZone;
use App\Models\PartnerProtectionSettings;
use Carbon\Carbon;

class PartnerValidationService
{
    /**
     * Valida un ordine prima dell'inserimento
     */
    public function validateOrder(
        int $restaurantId,
        ?int $deliveryZoneId = null,
        ?Carbon $requestedTime = null,
        bool $isBulky = false
    ): array {
        $errors = [];
        $warnings = [];
        $surcharges = [];

        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            return [
                'valid' => false,
                'errors' => ['Ristorante non trovato'],
                'warnings' => [],
                'surcharges' => [],
            ];
        }

        // 1. Verifica stato partner
        if ($restaurant->partner_status === 'suspended') {
            $errors[] = 'Il ristorante è attualmente sospeso: ' . ($restaurant->partner_suspension_reason ?? 'Contatta il supporto');
        } elseif ($restaurant->partner_status === 'warning') {
            $warnings[] = 'Il ristorante ha ricevuto avvertimenti recenti';
        }

        // 2. Verifica fascia oraria
        $checkTime = $requestedTime ?? Carbon::now();
        if (!RestaurantTimeSlot::isRestaurantOpen($restaurantId, $checkTime)) {
            $errors[] = 'Il ristorante non accetta ordini in questo orario';
        }

        // 3. Verifica zona di consegna
        if ($deliveryZoneId) {
            if (!RestaurantDeliveryZone::isZoneAllowedForRestaurant($restaurantId, $deliveryZoneId)) {
                $errors[] = 'La zona di consegna non è servita da questo ristorante';
            } else {
                // Controlla sovrapprezzo zona
                $zoneSurcharge = RestaurantDeliveryZone::getSurchargeForZone($restaurantId, $deliveryZoneId);
                if ($zoneSurcharge && $zoneSurcharge > 0) {
                    $surcharges[] = [
                        'type' => 'zone_surcharge',
                        'amount' => $zoneSurcharge,
                        'description' => 'Sovrapprezzo zona di consegna',
                    ];
                }
            }
        }

        // 4. Verifica ordine voluminoso
        if ($isBulky) {
            $settings = PartnerProtectionSettings::getEffectiveSettings($restaurantId);
            if ($settings['bulky_surcharge'] > 0) {
                $surcharges[] = [
                    'type' => 'bulky_surcharge',
                    'amount' => $settings['bulky_surcharge'],
                    'description' => 'Sovrapprezzo ordine voluminoso',
                ];
            }
        }

        // 5. Avvisi basati sulla storia del ristorante
        if ($restaurant->incident_count_30d >= 5) {
            $warnings[] = "Il ristorante ha avuto {$restaurant->incident_count_30d} segnalazioni negli ultimi 30 giorni";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'surcharges' => $surcharges,
            'total_surcharge' => array_sum(array_column($surcharges, 'amount')),
        ];
    }

    /**
     * Valida e applica il flag voluminoso
     */
    public function validateBulkyFlag(int $restaurantId, bool $isReportedBulky): array
    {
        $settings = PartnerProtectionSettings::getEffectiveSettings($restaurantId);

        $result = [
            'is_bulky' => $isReportedBulky,
            'surcharge' => $isReportedBulky ? $settings['bulky_surcharge'] : 0,
            'message' => null,
        ];

        if ($isReportedBulky) {
            $result['message'] = "Ordine voluminoso confermato. Sovrapprezzo: €{$settings['bulky_surcharge']}";
        }

        return $result;
    }

    /**
     * Verifica se un ristorante può accettare ordini
     */
    public function canRestaurantAcceptOrders(int $restaurantId): array
    {
        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            return ['can_accept' => false, 'reason' => 'Ristorante non trovato'];
        }

        if (!$restaurant->is_active) {
            return ['can_accept' => false, 'reason' => 'Ristorante non attivo'];
        }

        if ($restaurant->partner_status === 'suspended') {
            return [
                'can_accept' => false,
                'reason' => 'Ristorante sospeso',
                'suspension_reason' => $restaurant->partner_suspension_reason,
            ];
        }

        if (!RestaurantTimeSlot::isRestaurantOpen($restaurantId)) {
            return ['can_accept' => false, 'reason' => 'Ristorante chiuso'];
        }

        return ['can_accept' => true, 'reason' => null];
    }

    /**
     * Ottiene le zone di consegna disponibili per un ristorante
     */
    public function getAvailableDeliveryZones(int $restaurantId): array
    {
        return RestaurantDeliveryZone::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with('deliveryZone')
            ->get()
            ->map(function ($rdz) {
                return [
                    'id' => $rdz->delivery_zone_id,
                    'name' => $rdz->deliveryZone->name ?? 'Zona ' . $rdz->delivery_zone_id,
                    'surcharge' => $rdz->surcharge,
                ];
            })
            ->toArray();
    }

    /**
     * Ottiene gli orari di apertura per un ristorante
     */
    public function getRestaurantSchedule(int $restaurantId): array
    {
        $schedule = [];

        for ($day = 0; $day <= 6; $day++) {
            $slots = RestaurantTimeSlot::where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->whereNull('override_date')
                ->where(function ($q) use ($day) {
                    $q->where('day_of_week', $day)
                      ->orWhereNull('day_of_week');
                })
                ->get()
                ->map(function ($slot) {
                    return [
                        'type' => $slot->slot_type,
                        'start' => $slot->start_time,
                        'end' => $slot->end_time,
                    ];
                })
                ->toArray();

            $schedule[RestaurantTimeSlot::getDayLabel($day)] = $slots;
        }

        return $schedule;
    }

    /**
     * Ottiene i prossimi override (chiusure straordinarie)
     */
    public function getUpcomingOverrides(int $restaurantId, int $days = 30): array
    {
        return RestaurantTimeSlot::where('restaurant_id', $restaurantId)
            ->whereNotNull('override_date')
            ->where('override_date', '>=', Carbon::today())
            ->where('override_date', '<=', Carbon::today()->addDays($days))
            ->orderBy('override_date')
            ->get()
            ->map(function ($slot) {
                return [
                    'date' => $slot->override_date->format('Y-m-d'),
                    'is_closed' => $slot->is_closed_override,
                    'start_time' => $slot->is_closed_override ? null : $slot->start_time,
                    'end_time' => $slot->is_closed_override ? null : $slot->end_time,
                ];
            })
            ->toArray();
    }
}
