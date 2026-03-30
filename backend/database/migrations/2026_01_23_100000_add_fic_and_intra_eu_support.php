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
        // Add FIC integration columns to supplier_invoices
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('fic_id')->nullable()->after('id');
            $table->index('fic_id');
        });

        // Add intra-EU invoice support to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_intra_eu')->default(false)->after('invoice_type');
            $table->boolean('is_reverse_charge')->default(false)->after('is_intra_eu');
            $table->string('vat_country', 2)->nullable()->after('is_reverse_charge');
            $table->string('client_vat_number', 20)->nullable()->after('vat_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropIndex(['fic_id']);
            $table->dropColumn('fic_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_intra_eu', 'is_reverse_charge', 'vat_country', 'client_vat_number']);
        });
    }
};
