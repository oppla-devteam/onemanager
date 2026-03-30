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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');

            // Product information
            $table->string('category')->index(); // e.g., "BIRRE ARTIGIANALI"
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->integer('price_cents'); // Price in cents (e.g., 1050 = €10.50)

            // Availability flags
            $table->boolean('available_for_delivery')->default(true);
            $table->boolean('available_for_pickup')->default(true);
            $table->boolean('is_active')->default(true); // Master toggle

            // Media
            $table->string('image_url')->nullable();

            // Sorting
            $table->integer('sort_order')->default(0); // For manual ordering within category

            // Metadata
            $table->json('metadata')->nullable(); // For future extensions (allergens, etc.)

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['restaurant_id', 'category']);
            $table->index(['restaurant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
