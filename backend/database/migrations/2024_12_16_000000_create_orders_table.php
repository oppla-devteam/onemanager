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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            
            // Identificatori
            $table->string('oppla_order_id')->unique()->nullable(); // ID ordine da Oppla
            $table->string('order_number')->nullable();
            
            // Dati ordine
            $table->timestamp('order_date');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])->default('pending');
            
            // Dettagli prodotti (JSON)
            $table->json('items')->nullable(); // Array di prodotti
            $table->integer('items_count')->default(0);
            
            // Indirizzo spedizione
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_cap')->nullable();
            $table->string('shipping_country')->default('IT');
            
            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            
            // Fatturazione
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->boolean('is_invoiced')->default(false);
            
            // Sincronizzazione
            $table->timestamp('oppla_sync_at')->nullable();
            $table->json('oppla_data')->nullable(); // Dati completi da Oppla
            
            $table->timestamps();
            
            // Indici
            $table->index(['client_id', 'order_date']);
            $table->index(['status', 'order_date']);
            $table->index('is_invoiced');
            $table->index('oppla_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
