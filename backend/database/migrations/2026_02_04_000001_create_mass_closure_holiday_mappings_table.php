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
        Schema::create('mass_closure_holiday_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id');
            $table->uuid('oppla_holiday_id'); // UUID from OPPLA holidays table
            $table->uuid('oppla_restaurant_id'); // Restaurant UUID from OPPLA
            $table->string('restaurant_name')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')
                ->references('batch_id')
                ->on('mass_closure_batches')
                ->onDelete('cascade');

            $table->index('batch_id');
            $table->index('oppla_holiday_id');
            $table->index('oppla_restaurant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_closure_holiday_mappings');
    }
};
