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
        Schema::create('accounting_categories', function (Blueprint $table) {
            $table->id();
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['entrata', 'uscita']);
            $table->foreignId('parent_id')->nullable()->constrained('accounting_categories')->onDelete('cascade');
            
            $table->string('color', 7)->default('#6366f1'); // hex color
            $table->string('icon', 50)->nullable(); // icon name/class
            $table->text('description')->nullable();
            
            // Keywords per auto-categorizzazione
            $table->json('keywords')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('slug');
        });

        // Aggiungi colonna category_id a bank_transactions se non esiste
        if (!Schema::hasColumn('bank_transactions', 'category_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->constrained('accounting_categories')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('bank_transactions', 'category_id')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }
        
        Schema::dropIfExists('accounting_categories');
    }
};
