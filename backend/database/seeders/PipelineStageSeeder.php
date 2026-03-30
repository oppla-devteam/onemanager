<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            // Lead Stages
            [
                'name' => 'Nuovo Lead',
                'type' => 'lead',
                'order' => 1,
                'color' => '#94a3b8',
                'win_probability' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Contattato',
                'type' => 'lead',
                'order' => 2,
                'color' => '#3b82f6',
                'win_probability' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Qualificato',
                'type' => 'lead',
                'order' => 3,
                'color' => '#8b5cf6',
                'win_probability' => 30,
                'is_active' => true,
            ],
            
            // Opportunity Stages
            [
                'name' => 'Analisi Esigenze',
                'type' => 'opportunity',
                'order' => 4,
                'color' => '#06b6d4',
                'win_probability' => 40,
                'is_active' => true,
            ],
            [
                'name' => 'Proposta Inviata',
                'type' => 'opportunity',
                'order' => 5,
                'color' => '#10b981',
                'win_probability' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Negoziazione',
                'type' => 'opportunity',
                'order' => 6,
                'color' => '#f59e0b',
                'win_probability' => 75,
                'is_active' => true,
            ],
            [
                'name' => 'Contratto Inviato',
                'type' => 'opportunity',
                'order' => 7,
                'color' => '#eab308',
                'win_probability' => 90,
                'is_active' => true,
            ],
            
            // Final Stages
            [
                'name' => 'Chiuso Vinto',
                'type' => 'customer',
                'order' => 8,
                'color' => '#22c55e',
                'win_probability' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Chiuso Perso',
                'type' => 'lost',
                'order' => 9,
                'color' => '#ef4444',
                'win_probability' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($stages as $stage) {
            PipelineStage::updateOrCreate(
                ['name' => $stage['name']],
                $stage
            );
        }
    }
}
