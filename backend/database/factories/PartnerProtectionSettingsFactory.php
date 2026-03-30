<?php

namespace Database\Factories;

use App\Models\PartnerProtectionSettings;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerProtectionSettings>
 */
class PartnerProtectionSettingsFactory extends Factory
{
    protected $model = PartnerProtectionSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
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
    }

    /**
     * Create global settings (no restaurant).
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'restaurant_id' => null,
        ]);
    }
}
