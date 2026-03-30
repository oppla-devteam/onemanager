<?php

namespace Database\Factories;

use App\Models\PartnerIncident;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PartnerIncident>
 */
class PartnerIncidentFactory extends Factory
{
    protected $model = PartnerIncident::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $incidentType = fake()->randomElement([
            PartnerIncident::TYPE_DELAY,
            PartnerIncident::TYPE_FORGOTTEN_ITEM,
            PartnerIncident::TYPE_BULKY_UNMARKED,
        ]);

        return [
            'restaurant_id' => Restaurant::factory(),
            'incident_type' => $incidentType,
            'delay_minutes' => $incidentType === PartnerIncident::TYPE_DELAY
                ? fake()->numberBetween(5, 60)
                : null,
            'description' => match ($incidentType) {
                PartnerIncident::TYPE_DELAY => 'Ritardo nella consegna di ' . fake()->numberBetween(5, 60) . ' minuti.',
                PartnerIncident::TYPE_FORGOTTEN_ITEM => 'Prodotto dimenticato: ' . fake()->randomElement([
                    'bevanda', 'contorno', 'dessert', 'salsa', 'pane',
                ]) . '.',
                PartnerIncident::TYPE_BULKY_UNMARKED => 'Ordine voluminoso non segnalato al rider.',
                default => fake()->sentence(),
            },
            'status' => fake()->randomElement([
                PartnerIncident::STATUS_PENDING,
                PartnerIncident::STATUS_REVIEWED,
                PartnerIncident::STATUS_RESOLVED,
            ]),
        ];
    }

    /**
     * Indicate the incident is a delay.
     */
    public function delay(int $minutes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => PartnerIncident::TYPE_DELAY,
            'delay_minutes' => $minutes ?? fake()->numberBetween(5, 60),
        ]);
    }

    /**
     * Indicate the incident is a forgotten item.
     */
    public function forgottenItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'incident_type' => PartnerIncident::TYPE_FORGOTTEN_ITEM,
            'delay_minutes' => null,
        ]);
    }

    /**
     * Indicate the incident is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PartnerIncident::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate the incident is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PartnerIncident::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolution_notes' => 'Incidente risolto con il partner.',
        ]);
    }
}
