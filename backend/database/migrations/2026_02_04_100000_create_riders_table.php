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
        Schema::create('riders', function (Blueprint $table) {
            $table->id();

            // Tookan identifiers
            $table->string('fleet_id')->unique()->index()->comment('Tookan fleet ID (unique identifier)');
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // Contact info
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Status
            $table->enum('status', ['available', 'busy', 'offline'])->default('offline');
            $table->tinyInteger('status_code')->default(1)->comment('0=available, 1=offline, 2=busy');
            $table->boolean('is_blocked')->default(false);

            // Transport
            $table->string('transport_type')->default('motorcycle');
            $table->tinyInteger('transport_type_code')->default(0);

            // Location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Team/Organization
            $table->integer('team_id')->nullable()->index();
            $table->string('team_name')->nullable();

            // Additional data
            $table->text('tags')->nullable();
            $table->string('profile_image')->nullable();

            // Timestamps
            $table->timestamp('fleet_last_updated_at')->nullable()->comment('Last update timestamp from Tookan');
            $table->timestamp('last_synced_at')->nullable()->index()->comment('Last sync from Tookan to local DB');
            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
            $table->index(['team_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
