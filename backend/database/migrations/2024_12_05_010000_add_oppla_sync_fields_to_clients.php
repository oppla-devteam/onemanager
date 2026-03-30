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
        Schema::table('clients', function (Blueprint $table) {
            // Campi per sincronizzazione Opplà Admin
            $table->string('oppla_external_id')->nullable()->unique()->after('guid');
            $table->timestamp('oppla_sync_at')->nullable()->after('oppla_external_id');
            $table->json('oppla_restaurants')->nullable()->after('oppla_sync_at');
            $table->integer('oppla_restaurants_count')->default(0)->after('oppla_restaurants');
            
            // Indice per ricerche
            $table->index('oppla_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->dropColumn([
                'oppla_external_id',
                'oppla_sync_at',
                'oppla_restaurants',
                'oppla_restaurants_count',
            ]);
        });
    }
};
