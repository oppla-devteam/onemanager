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
        // Aggiungi campi sincronizzazione Oppla a partners
        Schema::table('partners', function (Blueprint $table) {
            $table->unsignedBigInteger('oppla_external_id')->nullable()->after('id');
            $table->timestamp('oppla_sync_at')->nullable()->after('is_active');
            
            $table->index('oppla_external_id');
        });

        // Aggiungi campi sincronizzazione Oppla a restaurants
        Schema::table('restaurants', function (Blueprint $table) {
            $table->unsignedBigInteger('oppla_external_id')->nullable()->after('id');
            $table->timestamp('oppla_sync_at')->nullable()->after('is_active');
            
            $table->index('oppla_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->dropColumn(['oppla_external_id', 'oppla_sync_at']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->dropColumn(['oppla_external_id', 'oppla_sync_at']);
        });
    }
};
