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
                                    $driver = DB::getDriverName();
                                    $hasIndex = false;

                                              if ($driver === 'sqlite') {
                                                                  // SQLite: usa pragma per verificare indici
                                        $indexes = DB::select("PRAGMA index_list('invoices')");
                                                                  $hasIndex = collect($indexes)->contains(fn ($idx) => $idx->name === 'invoices_numero_fattura_unique');
                                              } elseif ($driver === 'pgsql') {
                                                                  // PostgreSQL: query information_schema
                                        $indexes = DB::select("
                                                            SELECT constraint_name
                                                                                FROM information_schema.table_constraints
                                                                                                    WHERE table_name = 'invoices'
                                                                                                                        AND constraint_name = 'invoices_numero_fattura_unique'
                                                                                                                                            AND constraint_type = 'UNIQUE'
                                                                                                                                                            ");
                                                                  $hasIndex = !empty($indexes);
                                              } else {
                                                                  // MySQL
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
