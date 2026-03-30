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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('bozza', 'emessa', 'inviata', 'verificata', 'pagata', 'stornata') DEFAULT 'bozza'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('bozza', 'emessa', 'inviata', 'pagata', 'stornata') DEFAULT 'bozza'");
    }
};
