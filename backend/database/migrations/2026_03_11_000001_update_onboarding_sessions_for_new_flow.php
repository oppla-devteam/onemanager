<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_sessions', function (Blueprint $table) {
            // New step tracking columns
            $table->foreignId('partner_id')->nullable()->after('user_id')->constrained('partners')->onDelete('set null');
            $table->boolean('step_client_partner_completed')->default(false)->after('partner_id');
            $table->boolean('step_stripe_confirmed')->default(false)->after('step_client_partner_completed');
            $table->boolean('step_restaurant_completed')->default(false)->after('step_stripe_confirmed');

            // Remove old step columns
            $table->dropColumn([
                'step_owner_completed',
                'step_restaurants_completed',
                'step_covers_completed',
                'step_delivery_completed',
                'step_fees_completed',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_sessions', function (Blueprint $table) {
            // Restore old columns
            $table->boolean('step_owner_completed')->default(false);
            $table->boolean('step_restaurants_completed')->default(false);
            $table->boolean('step_covers_completed')->default(false);
            $table->boolean('step_delivery_completed')->default(false);
            $table->boolean('step_fees_completed')->default(false);

            // Remove new columns
            $table->dropForeign(['partner_id']);
            $table->dropColumn([
                'partner_id',
                'step_client_partner_completed',
                'step_stripe_confirmed',
                'step_restaurant_completed',
            ]);
        });
    }
};
