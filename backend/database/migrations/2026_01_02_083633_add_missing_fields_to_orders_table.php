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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('client_id');
            $table->decimal('subtotal', 10, 2)->default(0)->after('total_amount');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('subtotal');
            $table->decimal('discount', 10, 2)->default(0)->after('delivery_fee');
            $table->string('customer_name')->nullable()->after('order_number');
            $table->string('delivery_type')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'restaurant_id',
                'subtotal',
                'delivery_fee',
                'discount',
                'customer_name',
                'delivery_type',
            ]);
        });
    }
};
