<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge campi relativi alla protezione partner alle tabelle esistenti
     */
    public function up(): void
    {
        // Campi per deliveries
        Schema::table('deliveries', function (Blueprint $table) {
            // Flag ordine voluminoso
            $table->boolean('is_bulky')->default(false)->after('note');
            $table->boolean('bulky_reported_by_restaurant')->default(false)->after('is_bulky');
            $table->boolean('bulky_confirmed_by_rider')->default(false)->after('bulky_reported_by_restaurant');

            // Doppia consegna / ritorno
            $table->boolean('is_return_trip')->default(false)->after('bulky_confirmed_by_rider');
            $table->foreignId('original_delivery_id')->nullable()->after('is_return_trip');

            // Doppia consegna per dimenticanza
            $table->boolean('is_double_delivery')->default(false)->after('original_delivery_id');
            $table->string('double_delivery_reason')->nullable()->after('is_double_delivery');

            // Ritardo pickup
            $table->timestamp('expected_pickup_time')->nullable()->after('double_delivery_reason');
            $table->integer('pickup_delay_minutes')->nullable()->after('expected_pickup_time');

            // Indice per query performance
            $table->index(['is_bulky', 'bulky_reported_by_restaurant']);
            $table->index(['is_return_trip', 'original_delivery_id']);
        });

        // Campi per restaurants
        Schema::table('restaurants', function (Blueprint $table) {
            // Contatori cache per performance (aggiornati periodicamente)
            $table->integer('incident_count_30d')->default(0)->after('is_active');
            $table->integer('delay_count_30d')->default(0)->after('incident_count_30d');
            $table->integer('bulky_unmarked_count_30d')->default(0)->after('delay_count_30d');

            // Stato partner protection
            $table->enum('partner_status', ['active', 'warning', 'suspended'])->default('active')->after('bulky_unmarked_count_30d');
            $table->timestamp('partner_suspended_at')->nullable()->after('partner_status');
            $table->text('partner_suspension_reason')->nullable()->after('partner_suspended_at');

            $table->index('partner_status');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex(['is_bulky', 'bulky_reported_by_restaurant']);
            $table->dropIndex(['is_return_trip', 'original_delivery_id']);

            $table->dropColumn([
                'is_bulky',
                'bulky_reported_by_restaurant',
                'bulky_confirmed_by_rider',
                'is_return_trip',
                'original_delivery_id',
                'is_double_delivery',
                'double_delivery_reason',
                'expected_pickup_time',
                'pickup_delay_minutes',
            ]);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['partner_status']);

            $table->dropColumn([
                'incident_count_30d',
                'delay_count_30d',
                'bulky_unmarked_count_30d',
                'partner_status',
                'partner_suspended_at',
                'partner_suspension_reason',
            ]);
        });
    }
};
