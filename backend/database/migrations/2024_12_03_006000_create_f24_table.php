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
        Schema::create('f24_payments', function (Blueprint $table) {
            $table->id();
            
            $table->string('reference_number')->unique(); // Numero protocollo F24
            $table->enum('type', ['imposte', 'contributi', 'inps', 'inail', 'altro'])->default('imposte');
            
            $table->date('scadenza');
            $table->date('data_pagamento')->nullable();
            
            $table->decimal('importo', 10, 2);
            $table->string('currency', 3)->default('EUR');
            
            $table->string('codice_tributo')->nullable();
            $table->integer('mese_riferimento')->nullable();
            $table->integer('anno_riferimento');
            
            // Flusso approvazione
            $table->enum('status', ['ricevuto', 'in_verifica', 'approvato', 'pagato', 'rifiutato'])->default('ricevuto');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            // File
            $table->string('file_path')->nullable();
            $table->string('email_source')->nullable(); // Email da cui è stato ricevuto
            
            // Collegamento contabile
            $table->foreignId('bank_transaction_id')->nullable()->constrained('bank_transactions');
            
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'scadenza']);
            $table->index(['anno_riferimento', 'mese_riferimento']);
        });
        
        // Buste paga
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            
            $table->integer('month');
            $table->integer('year');
            
            $table->decimal('lordo', 10, 2);
            $table->decimal('netto', 10, 2);
            $table->decimal('contributi_inps', 10, 2)->default(0);
            $table->decimal('contributi_inail', 10, 2)->default(0);
            $table->decimal('irpef', 10, 2)->default(0);
            $table->decimal('altri_contributi', 10, 2)->default(0);
            
            // Giorni lavorativi
            $table->integer('giorni_lavorati')->default(0);
            $table->integer('giorni_ferie')->default(0);
            $table->integer('giorni_malattia')->default(0);
            
            // Flusso
            $table->enum('status', ['generata', 'verificata', 'approvata', 'pagata'])->default('generata');
            $table->date('data_pagamento')->nullable();
            
            // File
            $table->string('file_path')->nullable();
            
            // Collegamento F24 contributi
            $table->foreignId('f24_payment_id')->nullable()->constrained('f24_payments');
            
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->unique(['employee_id', 'month', 'year'], 'payslip_period_unique');
            $table->index(['employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('f24_payments');
    }
};
