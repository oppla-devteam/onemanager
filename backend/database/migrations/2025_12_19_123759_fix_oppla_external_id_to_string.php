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
        // Fix partners table
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->string('oppla_external_id', 191)->nullable()->change();
            $table->index('oppla_external_id');
        });

        // Fix restaurants table
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->string('oppla_external_id', 191)->nullable()->change();
            $table->index('oppla_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert partners table
        Schema::table('partners', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->unsignedBigInteger('oppla_external_id')->nullable()->change();
            $table->index('oppla_external_id');
        });

        // Revert restaurants table
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['oppla_external_id']);
            $table->unsignedBigInteger('oppla_external_id')->nullable()->change();
            $table->index('oppla_external_id');
        });
    }
};
