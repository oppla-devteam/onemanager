<?php

namespace Database\Factories;

use App\Models\Rider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rider>
 */
class RiderFactory extends Factory
{
    protected $model = Rider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'fleet_id' => fake()->unique()->numberBetween(10000, 99999),
            'username' => strtolower($firstName) . '.' . strtolower($lastName) . fake()->numerify('##'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+39 3## ### ####'),
            'status' => fake()->randomElement(['available', 'busy', 'offline']),
            'is_blocked' => false,
            'latitude' => fake()->randomFloat(8, 41.0, 45.5),
            'longitude' => fake()->randomFloat(8, 9.0, 15.0),
            'team_name' => fake()->randomElement([
                'Team Milano', 'Team Roma', 'Team Napoli', 'Team Torino',
                'Team Bologna', 'Team Firenze', 'Team Palermo', 'Team Genova',
            ]),
            'last_synced_at' => now(),
        ];
    }

    /**
     * Indicate the rider is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'is_blocked' => false,
        ]);
    }

    /**
     * Indicate the rider is busy.
     */
    public function busy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'busy',
        ]);
    }

    /**
     * Indicate the rider is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
        ]);
    }

    /**
     * Indicate the rider is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
            'status' => 'offline',
        ]);
    }
}
