<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $imponibile = fake()->randomFloat(2, 50, 5000);
        $ivaRate = 0.22;

        return [
            'guid' => fake()->uuid(),
            'type' => fake()->randomElement(['partner_oppla', 'cliente_extra', 'consumatore']),
            'ragione_sociale' => fake()->company(),
            'piva' => 'IT' . fake()->numerify('###########'),
            'codice_fiscale' => strtoupper(fake()->bothify('??????##?##?###?')),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('+39 ### ### ####'),
            'indirizzo' => fake()->streetAddress(),
            'citta' => fake()->city(),
            'provincia' => fake()->randomElement([
                'MI', 'RM', 'NA', 'TO', 'PA', 'GE', 'BO', 'FI',
                'BA', 'CT', 'VE', 'VR', 'ME', 'PD', 'TR', 'BS',
                'TA', 'RG', 'RE', 'MO', 'PR', 'PC', 'BG', 'CO',
            ]),
            'cap' => fake()->numerify('#####'),
            'nazione' => 'IT',
            'status' => fake()->randomElement(['active', 'inactive', 'prospect']),
            'has_domain' => fake()->boolean(60),
            'has_pos' => fake()->boolean(40),
            'has_delivery' => fake()->boolean(70),
            'fee_mensile' => fake()->randomFloat(2, 0, 500),
            'fee_ordine' => fake()->randomFloat(2, 0.50, 5.00),
            'fee_consegna_base' => fake()->randomFloat(2, 2.00, 10.00),
            'abbonamento_mensile' => fake()->randomFloat(2, 0, 200),
        ];
    }

    /**
     * Indicate the client is a partner OPPLA.
     */
    public function partnerOppla(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'partner_oppla',
            'status' => 'active',
            'has_delivery' => true,
        ]);
    }

    /**
     * Indicate the client is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate the client is a prospect.
     */
    public function prospect(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'prospect',
        ]);
    }
}
