<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Elimina eventuali fatture duplicate (mantieni solo la prima)
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                DELETE FROM invoices WHERE id NOT IN (
                    SELECT MIN(id) FROM invoices
                    GROUP BY numero_fattura, type, invoice_type, anno
                )
            ');
        } else {
            DB::statement('
                DELETE i1 FROM invoices i1
                INNER JOIN invoices i2
                WHERE i1.id > i2.id
                AND i1.numero_fattura = i2.numero_fattura
                AND i1.type = i2.type
                AND i1.invoice_type = i2.invoice_type
                AND i1.anno = i2.anno
            ');
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['numero_fattura', 'type', 'invoice_type', 'anno'], 'invoices_unique_constraint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_unique_constraint');
        });
    }
};
