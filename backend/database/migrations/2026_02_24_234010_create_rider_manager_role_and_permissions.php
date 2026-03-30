<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view-dashboard',
            'view-orders',
            'view-deliveries',
            'view-menu',
            'manage-riders',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'rider-manager', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);
    }

    public function down(): void
    {
        $role = Role::where('name', 'rider-manager')->first();
        if ($role) {
            $role->delete();
        }

        Permission::where('name', 'manage-riders')->delete();
    }
};
