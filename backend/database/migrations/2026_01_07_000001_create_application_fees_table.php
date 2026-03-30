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
        Schema::create('application_fees', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_fee_id')->unique(); // ID della application fee in Stripe (fee_xxx)
            $table->decimal('amount', 10, 2); // Importo commissione
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('created_at_stripe'); // Data creazione in Stripe
            $table->string('stripe_account_id'); // Account Stripe Connect del partner
            $table->string('partner_email')->nullable();
            $table->string('partner_name')->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('charge_id')->nullable(); // ID del charge associato
            $table->text('description')->nullable();
            $table->string('period_month'); // Periodo mese (YYYY-MM)
            $table->json('raw_data')->nullable(); // Dati grezzi da Stripe per riferimento
            $table->timestamps();
            
            // Indici per performance
            $table->index('stripe_account_id');
            $table->index('partner_email');
            $table->index('client_id');
            $table->index('period_month');
            $table->index('created_at_stripe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_fees');
    }
};
