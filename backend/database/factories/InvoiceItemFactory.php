<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantita = fake()->numberBetween(1, 10);
        $prezzoUnitario = fake()->randomFloat(2, 10, 500);
        $sconto = fake()->randomElement([0, 0, 0, 5, 10, 15]);
        $base = $quantita * $prezzoUnitario;
        $subtotale = round($base - ($base * $sconto / 100), 2);

        return [
            'invoice_id' => Invoice::factory(),
            'descrizione' => fake()->randomElement([
                'Servizio delivery mensile',
                'Fee piattaforma',
                'Abbonamento mensile',
                'Consegne periodo',
                'Commissioni ordini',
                'Servizio POS',
            ]),
            'quantita' => $quantita,
            'prezzo_unitario' => $prezzoUnitario,
            'sconto' => $sconto,
            'iva_percentuale' => 22.00,
            'subtotale' => $subtotale,
        ];
    }
}
