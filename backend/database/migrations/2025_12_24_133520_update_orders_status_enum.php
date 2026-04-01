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
                    $driver = DB::getDriverName();

                // SQLite does not support MODIFY COLUMN or ENUM — skip
                if ($driver === 'sqlite') {
                                return;
                }

                // PostgreSQL uses different syntax
                if ($driver === 'pgsql') {
                                // In PostgreSQL, status is already a varchar/string column — just ensure valid values
                        // No ENUM type needed; the column was created as varchar
                        return;
                }

                // MySQL
                DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded', 'created', 'rejected', 'completed') DEFAULT 'pending'");
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
                    $driver = DB::getDriverName();

                if ($driver === 'sqlite') {
                                return;
                }

                if ($driver === 'pgsql') {
                                return;
                }

                DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending'");
        }
    };
