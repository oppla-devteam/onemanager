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
        Schema::table('invoices', function (Blueprint $table) {
            if (DB::getDriverName() === 'sqlite') {
                // SQLite: usa pragma per verificare indici
                $indexes = DB::select("PRAGMA index_list('invoices')");
                $hasIndex = collect($indexes)->contains(fn ($idx) => $idx->name === 'invoices_numero_fattura_unique');
            } else {
                $indexes = DB::select("SHOW INDEX FROM invoices WHERE Key_name = 'invoices_numero_fattura_unique'");
                $hasIndex = !empty($indexes);
            }

            if ($hasIndex) {
                $table->dropUnique('invoices_numero_fattura_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Ripristina il vecchio constraint se necessario
            $table->unique('numero_fattura', 'invoices_numero_fattura_unique');
        });
    }
};
