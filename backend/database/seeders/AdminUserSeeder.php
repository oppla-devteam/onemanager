<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea ruolo super-admin se non esiste
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['guard_name' => 'web']
        );

        // Crea o aggiorna account admin Binatomy
        $admin = User::updateOrCreate(
            ['email' => 'info@binatomy.com'],
            [
                'name' => 'Admin Binatomy',
                'password' => Hash::make('!Binatomy&Oppla2026$'),
                'email_verified_at' => now(),
            ]
        );

        // Assegna ruolo super-admin
        $admin->assignRole($superAdminRole);

        $this->command->info("✅ Account admin creato/aggiornato con successo!");
        $this->command->info("Email: {$admin->email}");
        $this->command->info("Password: !Binatomy&Oppla2026$");
        $this->command->info("Ruolo: {$superAdminRole->name}");
    }
}
