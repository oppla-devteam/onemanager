<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->company() . ' ' . fake()->randomElement([
                'Ristorante', 'Trattoria', 'Osteria', 'Pizzeria', 'Taverna',
            ]),
            'indirizzo' => fake()->streetAddress(),
            'citta' => fake()->city(),
            'provincia' => fake()->randomElement([
                'MI', 'RM', 'NA', 'TO', 'PA', 'GE', 'BO', 'FI',
                'BA', 'CT', 'VE', 'VR', 'ME', 'PD', 'TR', 'BS',
            ]),
            'cap' => fake()->numerify('#####'),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('+39 ### ### ####'),
            'is_active' => fake()->boolean(85),
            'partner_status' => fake()->randomElement(['active', 'suspended', 'warning']),
            'client_id' => Client::factory(),
            'category' => fake()->randomElement([
                'Pizza', 'Sushi', 'Hamburger', 'Cucina italiana', 'Cucina cinese',
                'Kebab', 'Poke', 'Dolci', 'Panini', 'Cucina messicana',
                'Pesce', 'Vegetariano', 'Cucina giapponese', 'Gelato', 'Pasticceria',
            ]),
        ];
    }

    /**
     * Indicate the restaurant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'partner_status' => 'active',
        ]);
    }

    /**
     * Indicate the restaurant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'partner_status' => 'suspended',
            'partner_suspended_at' => now(),
            'partner_suspension_reason' => 'Superamento soglia incidenti',
        ]);
    }

    /**
     * Create a restaurant without a client.
     */
    public function withoutClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => null,
        ]);
    }
}
