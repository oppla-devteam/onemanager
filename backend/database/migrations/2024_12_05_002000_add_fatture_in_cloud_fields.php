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
        // Aggiungi fic_id a clients
        if (!Schema::hasColumn('clients', 'fic_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('fic_id')->nullable()->after('stripe_subscription_id');
                $table->index('fic_id');
            });
        }

        // Aggiungi campi Fatture in Cloud a invoices
        if (!Schema::hasColumn('invoices', 'fic_document_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('fic_document_id')->nullable()->after('stripe_transaction_id');
                $table->string('sdi_status')->nullable()->after('fic_document_id'); // sent, accepted, rejected
                $table->timestamp('sdi_sent_at')->nullable()->after('sdi_status');
                
                $table->index('fic_document_id');
                $table->index('sdi_status');
            });
        }

        // Aggiungi ddt_id a deliveries
        if (!Schema::hasColumn('deliveries', 'ddt_id')) {
            Schema::table('deliveries', function (Blueprint $table) {
                $table->foreignId('ddt_id')->nullable()->after('invoice_id')->constrained('invoices')->onDelete('set null');
                $table->index('ddt_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['fic_id']);
            $table->dropColumn('fic_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['fic_document_id']);
            $table->dropIndex(['sdi_status']);
            $table->dropColumn(['fic_document_id', 'sdi_status', 'sdi_sent_at']);
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropForeign(['ddt_id']);
            $table->dropIndex(['ddt_id']);
            $table->dropColumn('ddt_id');
        });
    }
};
