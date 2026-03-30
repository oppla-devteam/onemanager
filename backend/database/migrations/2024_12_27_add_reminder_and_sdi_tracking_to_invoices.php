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
        Schema::table('invoices', function (Blueprint $table) {
            // STEP 1: Aggiungi le colonne se non esistono già
            if (!Schema::hasColumn('invoices', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('payment_status');
            }
            
            if (!Schema::hasColumn('invoices', 'riferimento_fattura_id')) {
                $table->foreignId('riferimento_fattura_id')->nullable()->after('client_id')->constrained('invoices')->nullOnDelete();
            }
            
            // Colonne per reminder system
            if (!Schema::hasColumn('invoices', 'last_reminder_sent_at')) {
                $table->timestamp('last_reminder_sent_at')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'reminder_count')) {
                $table->integer('reminder_count')->default(0);
            }
            
            // Colonne per SDI tracking
            if (!Schema::hasColumn('invoices', 'sdi_status')) {
                $table->string('sdi_status')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sdi_protocol_number')) {
                $table->string('sdi_protocol_number')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sdi_receipt_date')) {
                $table->timestamp('sdi_receipt_date')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sdi_accepted_at')) {
                $table->timestamp('sdi_accepted_at')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sdi_rejected_at')) {
                $table->timestamp('sdi_rejected_at')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'sdi_rejection_reason')) {
                $table->text('sdi_rejection_reason')->nullable();
            }
            
            // STEP 2: Aggiungi indici per performance (solo se colonne esistono)
            $table->index('last_reminder_sent_at');
            $table->index('sdi_status');
            $table->index('reminder_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['riferimento_fattura_id']);
            $table->dropColumn([
                'last_reminder_sent_at',
                'reminder_count',
                'sdi_protocol_number',
                'sdi_receipt_date',
                'sdi_accepted_at',
                'sdi_rejected_at',
                'sdi_rejection_reason',
                'cancelled_at',
                'riferimento_fattura_id',
            ]);
        });
    }
};
