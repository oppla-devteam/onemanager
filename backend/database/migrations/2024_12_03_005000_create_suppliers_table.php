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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            
            // Dati anagrafici
            $table->string('ragione_sociale');
            $table->string('piva')->nullable();
            $table->string('codice_fiscale')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('pec')->nullable();
            
            // Indirizzo
            $table->string('indirizzo')->nullable();
            $table->string('citta')->nullable();
            $table->string('provincia', 2)->nullable();
            $table->string('cap', 5)->nullable();
            $table->string('nazione', 2)->default('IT');
            
            // Tipo fornitore
            $table->enum('type', ['italiano_sdi', 'estero', 'altro'])->default('italiano_sdi');
            $table->boolean('is_active')->default(true);
            
            // Pagamento
            $table->string('iban')->nullable();
            $table->integer('giorni_pagamento')->default(30);
            
            // Note
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['ragione_sociale', 'is_active']);
        });
        
        // Fatture passive
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            
            $table->string('numero_fattura');
            $table->date('data_emissione');
            $table->date('data_scadenza')->nullable();
            $table->date('data_pagamento')->nullable();
            
            // Importi
            $table->decimal('imponibile', 10, 2);
            $table->decimal('iva', 10, 2);
            $table->decimal('totale', 10, 2);
            
            // SDI
            $table->string('sdi_identifier')->nullable();
            $table->string('adi_file_path')->nullable();
            $table->string('pdf_file_path')->nullable();
            
            // Stato
            $table->enum('status', ['ricevuta', 'verificata', 'pagata', 'inviata_commercialista'])->default('ricevuta');
            $table->enum('payment_status', ['non_pagata', 'pagata'])->default('non_pagata');
            
            // Collegamento bancario
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts');
            
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['supplier_id', 'status']);
            $table->index('data_emissione');
            $table->unique(['supplier_id', 'numero_fattura', 'data_emissione'], 'supplier_invoice_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('suppliers');
    }
};
