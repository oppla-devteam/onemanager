<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fix per OAuth flow: durante la fase "pending" (prima del callback),
     * non abbiamo ancora company_id, access_token, refresh_token.
     * Questi campi devono essere nullable.
     */
    public function up(): void
    {
        Schema::table('fatture_in_cloud_connections', function (Blueprint $table) {
            $table->string('fic_company_id')->nullable()->change();
            $table->text('access_token')->nullable()->change();
            $table->text('refresh_token')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatture_in_cloud_connections', function (Blueprint $table) {
            $table->string('fic_company_id')->nullable(false)->change();
            $table->text('access_token')->nullable(false)->change();
            $table->text('refresh_token')->nullable(false)->change();
        });
    }
};
