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
        Schema::create('menu_imports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Import metadata
            $table->string('filename');
            $table->integer('total_rows');
            $table->integer('created_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('error_count')->default(0);

            // Import results
            $table->json('errors')->nullable(); // Detailed error information
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_imports');
    }
};
