<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AuthorizedBinkUser;

return new class extends Migration
{
    public function up(): void
    {
        AuthorizedBinkUser::firstOrCreate(
            ['bink_username' => 'fbnomade'],
            [
                'display_name' => 'Federico Nomade',
                'role' => 'admin',
                'permissions' => [],
            ]
        );
    }

    public function down(): void
    {
        AuthorizedBinkUser::where('bink_username', 'fbnomade')->delete();
    }
};
