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
        Schema::create('rider_statistics', function (Blueprint $table) {
            $table->id();

            // Snapshot metadata
            $table->timestamp('snapshot_time')->index()->comment('Time of this statistics snapshot');
            $table->enum('data_source', ['cron', 'manual', 'fallback'])->default('cron')->comment('Source of this snapshot');

            // Rider counts
            $table->integer('total_riders')->default(0);
            $table->integer('available_riders')->default(0);
            $table->integer('busy_riders')->default(0);
            $table->integer('offline_riders')->default(0);

            $table->timestamps();

            // Indexes for queries
            $table->index(['snapshot_time', 'data_source']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_statistics');
    }
};
