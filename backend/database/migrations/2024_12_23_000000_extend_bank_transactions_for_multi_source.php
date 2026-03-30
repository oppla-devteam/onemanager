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
        Schema::table('bank_transactions', function (Blueprint $table) {
            // Sorgente del pagamento
            $table->enum('source', ['stripe', 'bank', 'vivawallet', 'nexi', 'paypal', 'manual'])
                ->default('manual')
                ->after('bank_account_id');
            
            // ID transazione originale dalla sorgente
            $table->string('source_transaction_id')->nullable()->after('source');
            
            // Dati aggiuntivi specifici per sorgente (JSON)
            $table->json('source_data')->nullable()->after('source_transaction_id');
            
            // Ragione sociale/beneficiario normalizzato per aggregazione
            $table->string('normalized_beneficiary')->nullable()->after('beneficiario');
            
            // Collegamento al cliente
            $table->foreignId('client_id')->nullable()->after('normalized_beneficiary')
                ->constrained('clients')->onDelete('set null');
            
            // Per gestire commissioni
            $table->decimal('fee', 10, 2)->default(0)->after('amount');
            $table->decimal('net_amount', 12, 2)->nullable()->after('fee');
            
            // Indice per ricerche veloci
            $table->index(['source', 'transaction_date']);
            $table->index(['normalized_beneficiary', 'transaction_date']);
            $table->index(['client_id', 'transaction_date']);
            $table->unique(['source', 'source_transaction_id'], 'unique_source_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['source', 'transaction_date']);
            $table->dropIndex(['normalized_beneficiary', 'transaction_date']);
            $table->dropIndex(['client_id', 'transaction_date']);
            $table->dropUnique('unique_source_transaction');
            
            $table->dropForeign(['client_id']);
            $table->dropColumn([
                'source',
                'source_transaction_id',
                'source_data',
                'normalized_beneficiary',
                'client_id',
                'fee',
                'net_amount'
            ]);
        });
    }
};
