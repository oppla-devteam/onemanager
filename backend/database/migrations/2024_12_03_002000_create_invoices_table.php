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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            // Tipo e numerazione
            $table->enum('type', ['attiva', 'passiva'])->default('attiva');
            $table->enum('invoice_type', ['ordinaria', 'differita', 'nota_credito'])->default('ordinaria');
            $table->string('numero_fattura')->unique();
            $table->integer('anno');
            $table->integer('numero_progressivo');
            
            // Date
            $table->date('data_emissione');
            $table->date('data_scadenza')->nullable();
            $table->date('data_pagamento')->nullable();
            
            // Importi
            $table->decimal('imponibile', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('totale', 10, 2)->default(0);
            $table->decimal('ritenuta_acconto', 10, 2)->default(0);
            $table->decimal('totale_netto', 10, 2)->default(0);
            
            // Dati specifici
            $table->string('stripe_transaction_id')->nullable();
            
            // Fatture in Cloud integration
            $table->unsignedBigInteger('fic_document_id')->nullable();
            $table->string('sdi_status')->nullable(); // sent, accepted, rejected
            $table->timestamp('sdi_sent_at')->nullable();
            
            $table->string('sdi_file_path')->nullable();
            $table->string('pdf_file_path')->nullable();
            
            // Stato
            $table->enum('status', ['bozza', 'emessa', 'inviata', 'pagata', 'stornata'])->default('bozza');
            $table->enum('payment_status', ['non_pagata', 'parzialmente_pagata', 'pagata'])->default('non_pagata');
            $table->enum('payment_method', ['bonifico', 'carta', 'contanti', 'riba', 'altro'])->nullable();
            
            // Note
            $table->text('note')->nullable();
            $table->text('causale')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indici
            $table->index(['client_id', 'type']);
            $table->index(['data_emissione', 'status']);
            $table->index('numero_fattura');
            $table->index('stripe_transaction_id');
        });
        
        // Tabella per righe fattura
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            
            $table->string('descrizione');
            $table->decimal('quantita', 10, 2)->default(1);
            $table->decimal('prezzo_unitario', 10, 2);
            $table->decimal('sconto', 5, 2)->default(0);
            $table->decimal('iva_percentuale', 5, 2)->default(22);
            $table->decimal('subtotale', 10, 2);
            
            // Collegamento servizi
            $table->string('service_type')->nullable(); // domain, pos, delivery, upselling
            $table->string('service_id')->nullable();
            
            $table->timestamps();
            
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
