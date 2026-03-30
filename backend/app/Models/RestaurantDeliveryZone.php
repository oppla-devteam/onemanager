<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RestaurantDeliveryZone extends Model
{
    use HasFactory;

    protected $table = 'restaurant_delivery_zones';

    protected $fillable = [
        'restaurant_id',
        'delivery_zone_id',
        'is_active',
        'surcharge',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'surcharge' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Verifica se una zona è ammessa per un ristorante
     */
    public static function isZoneAllowedForRestaurant(int $restaurantId, int $deliveryZoneId): bool
    {
        return self::where('restaurant_id', $restaurantId)
            ->where('delivery_zone_id', $deliveryZoneId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Ottiene il sovrapprezzo per una zona specifica
     */
    public static function getSurchargeForZone(int $restaurantId, int $deliveryZoneId): ?float
    {
        $record = self::where('restaurant_id', $restaurantId)
            ->where('delivery_zone_id', $deliveryZoneId)
            ->where('is_active', true)
            ->first();

        return $record?->surcharge;
    }

    /**
     * Ottiene tutte le zone ammesse per un ristorante
     */
    public static function getActiveZonesForRestaurant(int $restaurantId): array
    {
        return self::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->with('deliveryZone')
            ->get()
            ->toArray();
    }
}
