<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fatture_in_cloud_connections', function (Blueprint $table) {
            $table->string('pending_oauth_state')->nullable()->after('user_id');
            $table->timestamp('oauth_state_expires_at')->nullable()->after('pending_oauth_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatture_in_cloud_connections', function (Blueprint $table) {
            $table->dropColumn(['pending_oauth_state', 'oauth_state_expires_at']);
        });
    }
};
