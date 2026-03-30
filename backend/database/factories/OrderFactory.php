<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Client;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(800, 8000); // in cents
        $deliveryFee = fake()->randomElement([0, 199, 299, 399, 499]);
        $discount = fake()->randomElement([0, 0, 0, 100, 200, 500]);
        $totalAmount = $subtotal + $deliveryFee - $discount;

        return [
            'client_id' => Client::factory(),
            'restaurant_id' => Restaurant::factory(),
            'oppla_order_id' => fake()->unique()->numerify('ORD-######'),
            'order_number' => fake()->unique()->numerify('ORD-2026-#####'),
            'customer_name' => fake()->name(),
            'order_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'currency' => 'EUR',
            'status' => fake()->randomElement(['pending', 'confirmed', 'delivered', 'cancelled']),
            'delivery_type' => fake()->randomElement(['delivery', 'takeaway']),
            'items_count' => fake()->numberBetween(1, 8),
            'shipping_address' => fake()->streetAddress(),
            'shipping_city' => fake()->city(),
            'shipping_province' => fake()->randomElement(['MI', 'RM', 'NA', 'TO', 'BO', 'FI']),
            'shipping_cap' => fake()->numerify('#####'),
            'shipping_country' => 'IT',
            'is_invoiced' => fake()->boolean(30),
        ];
    }

    /**
     * Indicate the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'delivered_at' => fake()->dateTimeBetween($attributes['order_date'] ?? '-1 month', 'now'),
        ]);
    }

    /**
     * Indicate the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
