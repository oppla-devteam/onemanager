<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PartnerProtectionSettings extends Model
{
    use HasFactory;

    protected $table = 'partner_protection_settings';

    protected $fillable = [
        'restaurant_id',
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
    ];

    protected $casts = [
        'delay_threshold_count' => 'integer',
        'delay_threshold_period_days' => 'integer',
        'delay_penalty_amount' => 'decimal:2',
        'forgotten_item_penalty' => 'decimal:2',
        'forgotten_item_double_delivery' => 'boolean',
        'bulky_surcharge' => 'decimal:2',
        'bulky_unmarked_penalty' => 'decimal:2',
        'bulky_unmarked_threshold' => 'integer',
        'bulky_unmarked_period_days' => 'integer',
        'bulky_repeated_penalty' => 'decimal:2',
        'double_delivery_multiplier' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Ottiene le impostazioni per un ristorante specifico o quelle globali
     */
    public static function getForRestaurant(?int $restaurantId): self
    {
        if ($restaurantId) {
            $settings = self::where('restaurant_id', $restaurantId)->first();
            if ($settings) {
                return $settings;
            }
        }

        // Ritorna impostazioni globali (restaurant_id = null)
        return self::whereNull('restaurant_id')->firstOrCreate(
            ['restaurant_id' => null],
            [
                'delay_threshold_count' => 3,
                'delay_threshold_period_days' => 30,
                'delay_penalty_amount' => 50.00,
                'forgotten_item_penalty' => 10.00,
                'forgotten_item_double_delivery' => true,
                'bulky_surcharge' => 3.00,
                'bulky_unmarked_penalty' => 15.00,
                'bulky_unmarked_threshold' => 3,
                'bulky_unmarked_period_days' => 30,
                'bulky_repeated_penalty' => 50.00,
                'double_delivery_multiplier' => 1.5,
            ]
        );
    }

    /**
     * Ottiene le impostazioni effettive (merge di globali e specifiche per ristorante)
     */
    public static function getEffectiveSettings(?int $restaurantId): array
    {
        $global = self::whereNull('restaurant_id')->first();
        $restaurant = $restaurantId ? self::where('restaurant_id', $restaurantId)->first() : null;

        $defaults = [
            'delay_threshold_count' => 3,
            'delay_threshold_period_days' => 30,
            'delay_penalty_amount' => 50.00,
            'forgotten_item_penalty' => 10.00,
            'forgotten_item_double_delivery' => true,
            'bulky_surcharge' => 3.00,
            'bulky_unmarked_penalty' => 15.00,
            'bulky_unmarked_threshold' => 3,
            'bulky_unmarked_period_days' => 30,
            'bulky_repeated_penalty' => 50.00,
            'double_delivery_multiplier' => 1.5,
        ];

        // Merge: defaults < global < restaurant
        $settings = $defaults;

        if ($global) {
            foreach ($defaults as $key => $value) {
                if ($global->$key !== null) {
                    $settings[$key] = $global->$key;
                }
            }
        }

        if ($restaurant) {
            foreach ($defaults as $key => $value) {
                if ($restaurant->$key !== null) {
                    $settings[$key] = $restaurant->$key;
                }
            }
        }

        return $settings;
    }
}
