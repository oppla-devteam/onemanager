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
        Schema::table('deliveries', function (Blueprint $table) {
            // IDs esterni OPPLA
            $table->string('oppla_id')->nullable()->after('id')->index(); // ID principale OPPLA
            $table->string('partner_id')->nullable()->after('oppla_id')->index(); // Partner ID
            $table->string('restaurant_id')->nullable()->after('partner_id')->index(); // Restaurant ID
            $table->string('user_id')->nullable()->after('restaurant_id'); // User ID
            $table->string('delivery_code')->nullable()->after('user_id')->index(); // Codice consegna (es. D-FGF-2J4)
            
            // Date e orari
            $table->timestamp('delivery_scheduled_at')->nullable()->after('order_date'); // Data/ora programmata consegna
            
            // Indirizzi e coordinate GPS
            $table->string('shipping_address')->nullable()->after('delivery_address'); // Indirizzo completo da OPPLA
            $table->text('gps_location')->nullable()->after('shipping_address'); // Coordinate GPS (PostGIS format)
            $table->string('delivery_notes')->nullable()->after('note'); // Note consegna (citofono, piano, etc)
            
            // Dati cliente finale
            $table->string('customer_name')->nullable()->after('delivery_notes'); // Nome cliente finale
            $table->string('customer_phone')->nullable()->after('customer_name'); // Telefono cliente
            
            // Importi dettagliati (in centesimi)
            $table->integer('original_amount')->nullable()->after('order_amount')->comment('Importo originale ordine (centesimi)');
            $table->string('payment_method')->nullable()->after('original_amount'); // Cash, Card
            $table->integer('platform_fee')->nullable()->after('payment_method')->comment('Fee piattaforma (centesimi)');
            $table->integer('distance_fee')->nullable()->after('platform_fee')->comment('Fee distanza (centesimi)');
            
            // IDs tracciamento pagamenti
            $table->string('platform_fee_id')->nullable()->after('distance_fee'); // ID fee piattaforma
            $table->string('distance_fee_id')->nullable()->after('platform_fee_id'); // ID fee distanza  
            $table->string('payment_intent')->nullable()->after('distance_fee_id'); // Stripe payment intent
            
            // Timestamp esterni
            $table->timestamp('oppla_created_at')->nullable()->after('updated_at'); // Created_at da OPPLA
            $table->timestamp('oppla_updated_at')->nullable()->after('oppla_created_at'); // Updated_at da OPPLA
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'oppla_id',
                'partner_id',
                'restaurant_id',
                'user_id',
                'delivery_code',
                'delivery_scheduled_at',
                'shipping_address',
                'gps_location',
                'delivery_notes',
                'customer_name',
                'customer_phone',
                'original_amount',
                'payment_method',
                'platform_fee',
                'distance_fee',
                'platform_fee_id',
                'distance_fee_id',
                'payment_intent',
                'oppla_created_at',
                'oppla_updated_at',
            ]);
        });
    }
};
