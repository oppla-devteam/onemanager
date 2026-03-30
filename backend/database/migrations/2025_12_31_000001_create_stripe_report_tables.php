<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crea tabella stripe_transactions se non esiste
        if (!Schema::hasTable('stripe_transactions')) {
            Schema::create('stripe_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique();
                $table->string('type'); // charge, transfer, payment, application_fee, etc.
                $table->string('source')->nullable(); // payment_intent, charge_id, etc.
                $table->decimal('amount', 10, 2);
                $table->decimal('fee', 10, 2)->default(0);
                $table->decimal('net', 10, 2);
                $table->string('currency', 3)->default('eur');
                $table->timestamp('created_at');
                $table->timestamp('available_on')->nullable();
                $table->boolean('manually_corrected')->default(false);
                $table->boolean('auto_corrected')->default(false);
                $table->string('correction_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('updated_at')->nullable();
                
                $table->index(['type', 'created_at']);
                $table->index('transaction_id');
            });
        }

        // Crea tabella settings se non esiste
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_transactions');
        Schema::dropIfExists('settings');
    }
};
