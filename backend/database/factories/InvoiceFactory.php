<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $imponibile = fake()->randomFloat(2, 50, 5000);
        $iva = round($imponibile * 0.22, 2);
        $totale = round($imponibile + $iva, 2);
        $progressivo = fake()->numberBetween(1, 200);
        $anno = 2026;
        $dataEmissione = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'client_id' => Client::factory(),
            'type' => fake()->randomElement(['attiva', 'passiva']),
            'invoice_type' => fake()->randomElement(['ordinaria', 'differita']),
            'numero_fattura' => $progressivo . '/A',
            'anno' => $anno,
            'numero_progressivo' => $progressivo,
            'data_emissione' => $dataEmissione,
            'data_scadenza' => fake()->dateTimeBetween($dataEmissione, '+90 days'),
            'imponibile' => $imponibile,
            'iva' => $iva,
            'totale' => $totale,
            'status' => fake()->randomElement(['bozza', 'emessa', 'inviata_sdi', 'pagata']),
            'payment_status' => fake()->randomElement(['non_pagata', 'pagata', 'parziale']),
        ];
    }

    /**
     * Indicate the invoice is active (attiva).
     */
    public function attiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'attiva',
        ]);
    }

    /**
     * Indicate the invoice is passive (passiva).
     */
    public function passiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'passiva',
        ]);
    }

    /**
     * Indicate the invoice has been paid.
     */
    public function pagata(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pagata',
            'payment_status' => 'pagata',
            'data_pagamento' => fake()->dateTimeBetween($attributes['data_emissione'] ?? '-3 months', 'now'),
        ]);
    }

    /**
     * Indicate the invoice is a draft.
     */
    public function bozza(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'bozza',
            'payment_status' => 'non_pagata',
        ]);
    }
}
