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
        // Servizi OPPLA base
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            $table->enum('category', ['oppla', 'upselling', 'extra'])->default('oppla');
            $table->enum('type', ['domain', 'pos', 'abbonamento', 'manutenzione', 'consegna', 'altro'])->default('altro');
            
            // Prezzi
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_period', ['one_time', 'monthly', 'yearly'])->default('monthly');
            $table->boolean('is_recurring')->default(false);
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            
            $table->text('features')->nullable(); // JSON
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index('slug');
        });
        
        // Servizi sottoscritti dai clienti
        Schema::create('client_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date')->nullable();
            
            // Prezzo personalizzato (se diverso dal servizio base)
            $table->decimal('custom_price', 10, 2)->nullable();
            
            $table->enum('status', ['active', 'suspended', 'cancelled', 'expired'])->default('active');
            $table->boolean('auto_renew')->default(true);
            
            // Configurazione specifica
            $table->text('configuration')->nullable(); // JSON
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->index(['client_id', 'status']);
            $table->index(['service_id', 'status']);
            $table->index('next_billing_date');
        });
        
        // Upselling services (servizi extra venduti)
        Schema::create('upselling_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            $table->string('product_name'); // zaini, packaging, etc
            $table->text('description')->nullable();
            
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->date('sale_date');
            
            // Fatturazione
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->boolean('is_invoiced')->default(false);
            
            $table->enum('status', ['pending', 'delivered', 'cancelled'])->default('pending');
            
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->index(['client_id', 'sale_date']);
            $table->index('is_invoiced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upselling_sales');
        Schema::dropIfExists('client_services');
        Schema::dropIfExists('services');
    }
};
