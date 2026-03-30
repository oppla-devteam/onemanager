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
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('tipo_societa', ['societa', 'ditta_individuale'])->default('societa')->after('type');
            $table->string('codice_fiscale_titolare')->nullable()->after('codice_fiscale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['tipo_societa', 'codice_fiscale_titolare']);
        });
    }
};
