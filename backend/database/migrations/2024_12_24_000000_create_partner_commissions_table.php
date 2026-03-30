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
        Schema::create('partner_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->string('partner_email')->index();
            $table->string('partner_name')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->decimal('commission_amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->dateTime('transaction_date');
            $table->string('order_id')->nullable();
            $table->text('description')->nullable();
            $table->json('stripe_data')->nullable();
            $table->string('period_month', 7)->index(); // Format: YYYY-MM
            $table->boolean('invoiced')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['partner_email', 'period_month']);
            $table->index(['client_id', 'period_month']);
            $table->index('invoiced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_commissions');
    }
};
