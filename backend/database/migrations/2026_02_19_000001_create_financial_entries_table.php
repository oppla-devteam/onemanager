<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('accounting_categories')
                ->onDelete('set null');

            $table->enum('entry_type', [
                'costo_fisso',
                'costo_variabile',
                'entrata_fissa',
                'entrata_variabile',
                'debito',
                'credito',
            ]);

            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->date('date');
            $table->date('due_date')->nullable();

            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_interval', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('next_renewal_date')->nullable();

            $table->string('vendor_name')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'paid', 'cancelled', 'overdue'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['entry_type', 'status']);
            $table->index('next_renewal_date');
            $table->index('due_date');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
