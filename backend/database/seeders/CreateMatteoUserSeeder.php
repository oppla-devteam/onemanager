<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateMatteoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Matteo Grafico',
            'email' => 'matteo.curti@oppla.delivery',
            'role' => 'viewer',
            'permissions' => json_encode([
                'clients',
                'contracts',
                'tasks'
            ]),
            'password' => Hash::make('MatteoGrafico2025!'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
