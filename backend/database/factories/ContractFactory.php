<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', '+1 month');
        $durationMonths = fake()->randomElement([6, 12, 24, 36]);

        return [
            'client_id' => Client::factory(),
            'title' => fake()->randomElement([
                'Contratto di servizio delivery',
                'Contratto partner logistico',
                'Contratto abbonamento piattaforma',
                'Contratto servizio POS',
                'Contratto dominio e hosting',
                'Accordo di partnership',
            ]),
            'contract_type' => fake()->randomElement(['servizio', 'fornitura', 'partnership']),
            'status' => fake()->randomElement(['bozza', 'attivo', 'in_scadenza', 'scaduto']),
            'start_date' => $startDate,
            'end_date' => (clone (new \DateTime($startDate->format('Y-m-d'))))->modify("+{$durationMonths} months"),
            'duration_months' => $durationMonths,
            'value' => fake()->randomFloat(2, 50, 2000),
            'currency' => 'EUR',
            'billing_frequency' => fake()->randomElement(['monthly', 'quarterly', 'yearly']),
            'auto_renew' => fake()->boolean(60),
        ];
    }

    /**
     * Indicate the contract is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate the contract has been sent for signing.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate the contract is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'signed_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'activated_at' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'start_date' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 years'),
        ]);
    }

    /**
     * Indicate the contract is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'start_date' => fake()->dateTimeBetween('-2 years', '-1 year'),
            'end_date' => fake()->dateTimeBetween('-6 months', '-1 day'),
        ]);
    }

    /**
     * Indicate the contract is signed.
     */
    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'signed',
            'signed_at' => now(),
            'signed_by' => fake()->name(),
        ]);
    }
}
