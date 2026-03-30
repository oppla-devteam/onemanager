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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->unique(); // ID ordine da piattaforma OPPLA
            
            // Tipo ordine
            $table->enum('order_type', ['cash', 'card', 'ricevuto'])->default('card');
            $table->boolean('is_partner_logistico')->default(false);
            
            // Indirizzi
            $table->string('pickup_address');
            $table->string('delivery_address');
            $table->decimal('distance_km', 5, 2)->default(0);
            
            // Importi
            $table->decimal('order_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee_base', 10, 2)->default(0);
            $table->decimal('delivery_fee_distance', 10, 2)->default(0);
            $table->decimal('delivery_fee_total', 10, 2)->default(0);
            $table->decimal('oppla_fee', 10, 2)->default(0);
            
            // Date e stato
            $table->timestamp('order_date');
            $table->timestamp('pickup_time')->nullable();
            $table->timestamp('delivery_time')->nullable();
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'delivering', 'delivered', 'cancelled'])->default('pending');
            
            // Rider
            $table->foreignId('rider_id')->nullable()->constrained('users');
            
            // Fatturazione
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->boolean('is_invoiced')->default(false);
            
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->index(['client_id', 'order_date']);
            $table->index(['status', 'order_date']);
            $table->index('is_invoiced');
        });
        
        // POS e ordini elettronici
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            $table->string('transaction_id')->unique();
            $table->string('pos_terminal_id')->nullable();
            
            $table->decimal('amount', 10, 2);
            $table->decimal('fee_oppla', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            
            $table->timestamp('transaction_date');
            $table->enum('payment_method', ['card', 'digital_wallet', 'contactless'])->default('card');
            
            // Collegamento Stripe
            $table->string('stripe_payment_intent_id')->nullable();
            
            // Fatturazione
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->boolean('is_invoiced')->default(false);
            
            $table->timestamps();
            
            $table->index(['client_id', 'transaction_date']);
            $table->index('is_invoiced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
        Schema::dropIfExists('deliveries');
    }
};
