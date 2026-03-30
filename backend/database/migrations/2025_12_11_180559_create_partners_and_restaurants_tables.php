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
        // Tabella Partners (Referenti Ristoranti)
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cognome');
            $table->string('email')->unique();
            $table->string('telefono')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabella Restaurants
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('nome');
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('piva')->nullable();
            $table->string('codice_fiscale')->nullable();
            
            // Cover, Logo e Foto
            $table->string('logo_path')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('cover_path')->nullable();
            
            // Dati per generazione cover
            $table->integer('cover_opacity')->default(50); // 0-100
            
            // Gestione Consegne
            $table->enum('delivery_management', ['oppla', 'autonomous'])->default('oppla');
            $table->json('delivery_zones')->nullable(); // Array di zone per consegne gestite da Oppla
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
        Schema::dropIfExists('restaurants');
    }
};
