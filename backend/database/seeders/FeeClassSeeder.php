<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeClass;

class FeeClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feeClasses = [
            // Consegna Autonoma + Miglior Prezzo
            [
                'name' => 'Autonoma - Miglior Prezzo',
                'description' => 'Ristorante gestisce le proprie consegne con opzione miglior prezzo',
                'delivery_type' => 'autonomous',
                'best_price' => true,
                'monthly_fee' => 29.00,
                'order_fee_percentage' => 8.00,
                'order_fee_fixed' => 0.30,
                'delivery_base_fee' => 0.00,
                'delivery_km_fee' => 0.00,
                'payment_processing_fee' => 1.50,
                'platform_fee' => 5.00,
                'is_active' => true,
            ],
            
            // Consegna Autonoma + NON Miglior Prezzo
            [
                'name' => 'Autonoma - Standard',
                'description' => 'Ristorante gestisce le proprie consegne senza opzione miglior prezzo',
                'delivery_type' => 'autonomous',
                'best_price' => false,
                'monthly_fee' => 49.00,
                'order_fee_percentage' => 12.00,
                'order_fee_fixed' => 0.50,
                'delivery_base_fee' => 0.00,
                'delivery_km_fee' => 0.00,
                'payment_processing_fee' => 2.00,
                'platform_fee' => 8.00,
                'is_active' => true,
            ],
            
            // Consegna Gestita FLA + Miglior Prezzo
            [
                'name' => 'Gestita FLA - Miglior Prezzo',
                'description' => 'Consegne gestite da FLA con rider proprietari e opzione miglior prezzo',
                'delivery_type' => 'managed',
                'best_price' => true,
                'monthly_fee' => 99.00,
                'order_fee_percentage' => 5.00,
                'order_fee_fixed' => 0.20,
                'delivery_base_fee' => 2.50,
                'delivery_km_fee' => 0.80,
                'payment_processing_fee' => 1.20,
                'platform_fee' => 3.00,
                'is_active' => true,
            ],
            
            // Consegna Gestita FLA + NON Miglior Prezzo
            [
                'name' => 'Gestita FLA - Premium',
                'description' => 'Consegne gestite da FLA con rider proprietari, massima visibilità',
                'delivery_type' => 'managed',
                'best_price' => false,
                'monthly_fee' => 149.00,
                'order_fee_percentage' => 10.00,
                'order_fee_fixed' => 0.40,
                'delivery_base_fee' => 3.00,
                'delivery_km_fee' => 1.00,
                'payment_processing_fee' => 1.80,
                'platform_fee' => 6.00,
                'is_active' => true,
            ],
        ];

        foreach ($feeClasses as $feeClass) {
            FeeClass::create($feeClass);
        }
    }
}
