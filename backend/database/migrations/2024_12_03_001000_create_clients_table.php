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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('guid')->unique();
            $table->enum('type', ['partner_oppla', 'cliente_extra', 'consumatore'])->default('partner_oppla');
            
            // Dati anagrafici
            $table->string('ragione_sociale');
            $table->string('piva')->nullable();
            $table->string('codice_fiscale')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('pec')->nullable();
            $table->string('sdi_code')->nullable();
            
            // Indirizzi
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('nazione', 2)->default('IT');
            
            // Dati fatturazione
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('stripe_subscription_id')->nullable();
            
            // Servizi attivi
            $table->boolean('has_domain')->default(false);
            $table->boolean('has_pos')->default(false);
            $table->boolean('has_delivery')->default(false);
            $table->boolean('is_partner_logistico')->default(false);
            
            // Fee e abbonamenti
            $table->decimal('fee_mensile', 10, 2)->default(0);
            $table->decimal('fee_ordine', 10, 2)->default(0);
            $table->decimal('fee_consegna_base', 10, 2)->default(0);
            $table->decimal('fee_consegna_km', 10, 2)->default(0);
            $table->decimal('abbonamento_mensile', 10, 2)->default(0);
            
            // Stato
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->date('onboarding_date')->nullable();
            $table->date('activation_date')->nullable();
            
            // Note
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index(['type', 'status']);
            $table->index('email');
            $table->index('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
