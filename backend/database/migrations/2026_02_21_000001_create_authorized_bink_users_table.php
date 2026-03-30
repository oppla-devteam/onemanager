<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\AuthorizedBinkUser;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorized_bink_users', function (Blueprint $table) {
            $table->id();
            $table->string('bink_username')->unique();
            $table->string('display_name')->nullable();
            $table->string('role')->default('viewer');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // Seed admin user
        AuthorizedBinkUser::create([
            'bink_username' => 'lorenzomoschella',
            'display_name' => 'Lorenzo Moschella',
            'role' => 'admin',
            'permissions' => [],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('authorized_bink_users');
    }
};
