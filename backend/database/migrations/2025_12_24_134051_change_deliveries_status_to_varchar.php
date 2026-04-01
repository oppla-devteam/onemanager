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

                if ($driver === 'sqlite') {
                                return;
                }

                // PostgreSQL uses ALTER COLUMN ... TYPE syntax
                if ($driver === 'pgsql') {
                                DB::statement("ALTER TABLE deliveries ALTER COLUMN status TYPE VARCHAR(50)");
                                DB::statement("ALTER TABLE deliveries ALTER COLUMN status SET DEFAULT 'pending'");
                                return;
                }

                // MySQL
                DB::statement("ALTER TABLE deliveries MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
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
                                // No-op: PostgreSQL cannot easily revert to ENUM
                        return;
                }

                DB::statement("ALTER TABLE deliveries MODIFY COLUMN status ENUM('pending', 'assigned', 'picked_up', 'delivering', 'delivered', 'cancelled') DEFAULT 'pending'");
        }
    };
