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
        Schema::table('leads', function (Blueprint $table) {
            $table->datetime('converted_to_client_at')->nullable()->after('converted_at');
            $table->datetime('converted_to_opportunity_at')->nullable()->after('converted_to_client_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['converted_to_client_at', 'converted_to_opportunity_at']);
        });
    }
};
