<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $orderDate = fake()->dateTimeBetween('-3 months', 'now');
        $feeBase = fake()->randomFloat(2, 2.00, 8.00);
        $feeDistance = fake()->randomFloat(2, 0.50, 5.00);

        return [
            'client_id' => Client::factory(),
            'order_id' => 'ORD-' . fake()->unique()->numerify('######'),
            'order_type' => fake()->randomElement(['delivery', 'takeaway']),
            'is_partner_logistico' => fake()->boolean(70),
            'pickup_address' => fake()->streetAddress() . ', ' . fake()->city(),
            'delivery_address' => fake()->streetAddress() . ', ' . fake()->city(),
            'distance_km' => fake()->randomFloat(2, 0.5, 15.0),
            'order_amount' => fake()->randomFloat(2, 10.00, 150.00),
            'delivery_fee_base' => $feeBase,
            'delivery_fee_distance' => $feeDistance,
            'delivery_fee_total' => round($feeBase + $feeDistance, 2),
            'oppla_fee' => fake()->randomFloat(2, 0.50, 3.00),
            'order_date' => $orderDate,
            'status' => fake()->randomElement([
                'in_attesa', 'assegnata', 'in_consegna', 'completata', 'annullata',
            ]),
            'is_invoiced' => fake()->boolean(40),
        ];
    }

    /**
     * Indicate the delivery is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completata',
            'delivery_time' => now(),
        ]);
    }

    /**
     * Indicate the delivery is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_consegna',
        ]);
    }

    /**
     * Indicate the delivery is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'annullata',
        ]);
    }

    /**
     * Indicate the delivery has been invoiced.
     */
    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_invoiced' => true,
        ]);
    }
}
