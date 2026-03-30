<?php

namespace Database\Factories;

use App\Models\PartnerPenalty;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerPenalty>
 */
class PartnerPenaltyFactory extends Factory
{
    protected $model = PartnerPenalty::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodStart = fake()->dateTimeBetween('-3 months', '-1 month');

        return [
            'restaurant_id' => Restaurant::factory(),
            'penalty_type' => fake()->randomElement([
                PartnerPenalty::TYPE_DELAY_THRESHOLD,
                PartnerPenalty::TYPE_FORGOTTEN_ITEM,
                PartnerPenalty::TYPE_BULKY_UNMARKED,
            ]),
            'amount' => fake()->randomFloat(2, 10, 100),
            'currency' => 'EUR',
            'billing_status' => fake()->randomElement([
                PartnerPenalty::STATUS_PENDING,
                PartnerPenalty::STATUS_INVOICED,
                PartnerPenalty::STATUS_PAID,
                PartnerPenalty::STATUS_WAIVED,
            ]),
            'period_start' => $periodStart,
            'period_end' => fake()->dateTimeBetween($periodStart, 'now'),
        ];
    }

    /**
     * Indicate the penalty is pending billing.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_status' => PartnerPenalty::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate the penalty has been invoiced.
     */
    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_status' => PartnerPenalty::STATUS_INVOICED,
        ]);
    }

    /**
     * Indicate the penalty has been waived.
     */
    public function waived(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_status' => PartnerPenalty::STATUS_WAIVED,
        ]);
    }

    /**
     * Create a delay threshold penalty.
     */
    public function delayThreshold(): static
    {
        return $this->state(fn (array $attributes) => [
            'penalty_type' => PartnerPenalty::TYPE_DELAY_THRESHOLD,
            'description' => 'Superamento soglia ritardi nel periodo.',
        ]);
    }
}
