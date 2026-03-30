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
        // Tabella Zone di Consegna FLA
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // es: "Centro Milano", "Zona Nord"
            $table->string('city');
            $table->text('description')->nullable();
            $table->json('postal_codes')->nullable(); // Array di CAP coperti
            $table->json('price_ranges'); // [{from_km: 0, to_km: 3, price: 3.50}, ...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabella Classi di Fee
        Schema::create('fee_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome identificativo della classe
            $table->string('description')->nullable();
            
            // Tipo di consegna e miglior prezzo
            $table->enum('delivery_type', ['autonomous', 'managed']); // autonoma o gestita FLA
            $table->boolean('best_price')->default(false);
            
            // Fee mensili
            $table->decimal('monthly_fee', 10, 2)->default(0); // Canone mensile
            
            // Fee per ordine
            $table->decimal('order_fee_percentage', 5, 2)->default(0); // % su ordine
            $table->decimal('order_fee_fixed', 10, 2)->default(0); // Fee fissa per ordine
            
            // Fee consegna
            $table->decimal('delivery_base_fee', 10, 2)->default(0); // Fee base consegna
            $table->decimal('delivery_km_fee', 10, 2)->default(0); // Fee per km
            
            // Fee aggiuntive
            $table->decimal('payment_processing_fee', 5, 2)->default(0); // % elaborazione pagamento
            $table->decimal('platform_fee', 5, 2)->default(0); // % commissione piattaforma
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indice univoco per tipo consegna + miglior prezzo
            $table->unique(['delivery_type', 'best_price', 'name']);
        });

        // Aggiorna tabella restaurants per collegare fee_class
        Schema::table('restaurants', function (Blueprint $table) {
            $table->foreignId('fee_class_id')->nullable()->after('delivery_zones')->constrained('fee_classes')->onDelete('set null');
            $table->boolean('best_price')->default(false)->after('fee_class_id');
            $table->string('category')->nullable()->after('nome'); // Categoria cucina
            $table->text('description')->nullable()->after('category'); // Descrizione locale
            $table->string('zone')->nullable()->after('provincia'); // Area/zona città
        });

        // Tabella Onboarding Status
        Schema::create('onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Chi fa onboarding
            
            // Step completati
            $table->boolean('step_owner_completed')->default(false);
            $table->boolean('step_restaurants_completed')->default(false);
            $table->boolean('step_covers_completed')->default(false);
            $table->boolean('step_delivery_completed')->default(false);
            $table->boolean('step_fees_completed')->default(false);
            
            // Step corrente
            $table->integer('current_step')->default(1); // 1-5
            
            // Dati temporanei
            $table->json('temp_data')->nullable();
            
            // Stato
            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropForeign(['fee_class_id']);
            $table->dropColumn(['fee_class_id', 'best_price', 'category', 'description', 'zone']);
        });
        
        Schema::dropIfExists('onboarding_sessions');
        Schema::dropIfExists('fee_classes');
        Schema::dropIfExists('delivery_zones');
    }
};
