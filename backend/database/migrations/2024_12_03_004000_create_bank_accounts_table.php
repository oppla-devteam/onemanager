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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            
            $table->string('name'); // es: "Conto Corrente Principale"
            $table->string('bank_name'); // es: "Intesa San Paolo"
            $table->string('iban')->unique();
            $table->enum('type', ['corrente', 'stripe', 'vivawallet', 'altro'])->default('corrente');
            
            // Credenziali API (se applicabile)
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->text('api_config')->nullable(); // JSON config
            
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync')->default(false);
            
            $table->decimal('saldo_iniziale', 12, 2)->default(0);
            $table->decimal('saldo_attuale', 12, 2)->default(0);
            $table->date('saldo_data')->nullable();
            
            $table->text('note')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
        });
        
        // Estratti conto
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->onDelete('cascade');
            
            $table->integer('month');
            $table->integer('year');
            $table->date('period_start');
            $table->date('period_end');
            
            $table->decimal('saldo_iniziale', 12, 2);
            $table->decimal('saldo_finale', 12, 2);
            $table->decimal('totale_entrate', 12, 2)->default(0);
            $table->decimal('totale_uscite', 12, 2)->default(0);
            
            // Files
            $table->string('excel_file_path')->nullable();
            $table->string('pdf_file_path')->nullable();
            
            $table->enum('status', ['pending', 'imported', 'verified', 'reconciled'])->default('pending');
            $table->date('imported_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['bank_account_id', 'month', 'year'], 'bank_statement_period_unique');
            $table->index(['bank_account_id', 'year', 'month']);
        });
        
        // Movimenti bancari
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_statement_id')->nullable()->constrained('bank_statements')->onDelete('set null');
            
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            
            $table->enum('type', ['entrata', 'uscita', 'bonifico', 'addebito', 'carta', 'altro'])->default('altro');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EUR');
            
            $table->string('descrizione')->nullable();
            $table->string('causale')->nullable();
            $table->string('beneficiario')->nullable();
            
            // Riconciliazione
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->unsignedBigInteger('supplier_invoice_id')->nullable();
            
            // Categorizzazione
            $table->string('category')->nullable();
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['is_reconciled', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('bank_accounts');
    }
};
