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
        Schema::create('mass_closure_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->integer('total_restaurants')->default(0);
            $table->integer('successful_closures')->default(0);
            $table->integer('failed_closures')->default(0);
            $table->text('output')->nullable();
            $table->timestamps();

            $table->index('batch_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_closure_batches');
    }
};
