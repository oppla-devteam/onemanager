<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateMatteoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')
            ->where('email', 'matteo.curti@oppla.delivery')
            ->update([
                'role' => 'viewer',
                'permissions' => json_encode(['clients', 'contracts', 'tasks']),
                'password' => Hash::make('MatteoGrafico2025!'),
                'updated_at' => now(),
            ]);

        echo "Utente Matteo aggiornato con successo\n";
    }
}
