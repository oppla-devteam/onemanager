<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds geometry field for storing GeoJSON polygons for delivery zones.
     * This enables map visualization and drawing of delivery areas.
     */
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            // GeoJSON geometry stored as JSON (polygon coordinates)
            // Format: {"type": "Polygon", "coordinates": [[[lng, lat], [lng, lat], ...]]}
            $table->json('geometry')->nullable()->after('price_ranges');
            
            // Center point for quick map centering
            $table->decimal('center_lat', 10, 7)->nullable()->after('geometry');
            $table->decimal('center_lng', 10, 7)->nullable()->after('center_lat');
            
            // Zone color for map visualization
            $table->string('color', 7)->default('#3b82f6')->after('center_lng');
            
            // Source of the zone (oppla_sync, manual, imported)
            $table->string('source')->default('manual')->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn(['geometry', 'center_lat', 'center_lng', 'color', 'source']);
        });
    }
};
