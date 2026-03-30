<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreatePedroUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Controlla se l'utente esiste già
        $existing = User::where('email', 'pedro@binatomy.com')->first();

        if ($existing) {
            $this->command->warn('⚠️  Utente Pedro già esistente, aggiorno password...');
            $existing->update([
                'password' => Hash::make('PedroBinatomy2026$87'),
                'updated_at' => now(),
            ]);
            $this->command->info('Password aggiornata per pedro@binatomy.com');
        } else {
            DB::table('users')->insert([
                'name' => 'Pedro',
                'email' => 'pedro@binatomy.com',
                'role' => 'viewer',
                'permissions' => json_encode([
                    'clients',
                    'contracts',
                    'tasks'
                ]),
                'password' => Hash::make('PedroBinatomy2026$87'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Utente Pedro creato con successo!');
        }

        $this->command->info('');
        $this->command->info('📋 Credenziali:');
        $this->command->info('   Email: pedro@binatomy.com');
        $this->command->info('   Password: PedroBinatomy2026$87');
        $this->command->info('   Ruolo: viewer (sola lettura)');
    }
}
