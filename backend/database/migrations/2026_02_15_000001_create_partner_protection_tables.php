<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modulo Protezione Partner - Tabelle per gestire segnalazioni, penali e configurazioni
     * per tutelare le aziende partner che forniscono i rider
     */
    public function up(): void
    {
        // Tabella impostazioni globali e per ristorante
        Schema::create('partner_protection_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->nullable()->constrained()->onDelete('cascade');

            // Soglie ritardo
            $table->integer('delay_threshold_count')->default(3); // N ritardi nel periodo
            $table->integer('delay_threshold_period_days')->default(30); // Periodo in giorni
            $table->decimal('delay_penalty_amount', 10, 2)->default(50.00); // Penale per superamento soglia

            // Dimenticanza prodotti
            $table->decimal('forgotten_item_penalty', 10, 2)->default(10.00); // Penale per dimenticanza
            $table->boolean('forgotten_item_double_delivery')->default(true); // Crea consegna di ritorno

            // Ordini voluminosi
            $table->decimal('bulky_surcharge', 10, 2)->default(3.00); // Sovrapprezzo ordine voluminoso
            $table->decimal('bulky_unmarked_penalty', 10, 2)->default(15.00); // Penale se non segnalato
            $table->integer('bulky_unmarked_threshold')->default(3); // N volte prima della penale cumulativa
            $table->integer('bulky_unmarked_period_days')->default(30);
            $table->decimal('bulky_repeated_penalty', 10, 2)->default(50.00); // Penale per violazioni ripetute

            // Doppia consegna
            $table->decimal('double_delivery_multiplier', 5, 2)->default(1.5); // Moltiplicatore tariffa

            $table->timestamps();

            // Se restaurant_id è null, sono le impostazioni globali
            $table->unique('restaurant_id');
        });

        // Tabella segnalazioni/incidenti
        Schema::create('partner_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_id')->nullable()->constrained()->onDelete('set null');
            $table->string('rider_fleet_id')->nullable(); // ID Tookan del rider
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('incident_type', [
                'delay',              // Ritardo nella consegna al rider
                'forgotten_item',     // Ristorante ha dimenticato prodotti
                'bulky_unmarked',     // Ordine voluminoso non segnalato
                'packaging_issue',    // Problema con packaging
                'other'               // Altro
            ]);

            $table->integer('delay_minutes')->nullable(); // Solo per tipo delay
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Dati aggiuntivi (foto, ecc.)

            $table->enum('status', ['pending', 'reviewed', 'resolved', 'disputed'])->default('pending');
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            $table->foreignId('penalty_id')->nullable(); // Collegamento alla penale generata

            $table->timestamps();
            $table->softDeletes();

            $table->index(['restaurant_id', 'incident_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        // Tabella penali
        Schema::create('partner_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('penalty_type', [
                'delay_threshold',    // Superamento soglia ritardi
                'forgotten_item',     // Dimenticanza singola
                'bulky_unmarked',     // Voluminoso non segnalato singolo
                'bulky_repeated',     // Voluminosi non segnalati ripetuti
                'double_delivery',    // Costo doppia consegna
                'other'               // Altro
            ]);

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');

            $table->enum('billing_status', ['pending', 'invoiced', 'paid', 'waived'])->default('pending');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');

            $table->text('description')->nullable();
            $table->json('incident_ids')->nullable(); // Array di ID incidenti collegati

            $table->date('period_start')->nullable(); // Inizio periodo (per soglie)
            $table->date('period_end')->nullable(); // Fine periodo

            $table->timestamps();
            $table->softDeletes();

            $table->index(['restaurant_id', 'billing_status']);
            $table->index(['billing_status', 'created_at']);
        });

        // Tabella fasce orarie ristoranti
        Schema::create('restaurant_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');

            $table->tinyInteger('day_of_week')->nullable(); // 0=domenica, 1=lunedi, ..., 6=sabato, null=tutti
            $table->enum('slot_type', ['lunch', 'dinner', 'all_day', 'custom'])->default('all_day');

            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('is_active')->default(true);

            // Override per date specifiche (festività, chiusure straordinarie)
            $table->date('override_date')->nullable();
            $table->boolean('is_closed_override')->default(false);

            $table->timestamps();

            $table->index(['restaurant_id', 'day_of_week', 'is_active']);
            $table->index(['restaurant_id', 'override_date']);
        });

        // Tabella zone di consegna ammesse per ristorante
        Schema::create('restaurant_delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_zone_id')->constrained()->onDelete('cascade');

            $table->boolean('is_active')->default(true);
            $table->decimal('surcharge', 10, 2)->nullable(); // Sovrapprezzo opzionale per questa zona

            $table->timestamps();

            $table->unique(['restaurant_id', 'delivery_zone_id']);
        });

        // Aggiungi foreign key per penalty_id dopo che la tabella penalties esiste
        Schema::table('partner_incidents', function (Blueprint $table) {
            $table->foreign('penalty_id')->references('id')->on('partner_penalties')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('partner_incidents', function (Blueprint $table) {
            $table->dropForeign(['penalty_id']);
        });

        Schema::dropIfExists('restaurant_delivery_zones');
        Schema::dropIfExists('restaurant_time_slots');
        Schema::dropIfExists('partner_penalties');
        Schema::dropIfExists('partner_incidents');
        Schema::dropIfExists('partner_protection_settings');
    }
};
