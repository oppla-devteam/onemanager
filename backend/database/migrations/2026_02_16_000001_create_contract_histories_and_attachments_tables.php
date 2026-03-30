<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contract_histories')) {
            Schema::create('contract_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->string('old_status')->nullable();
                $table->string('new_status')->nullable();
                $table->json('changes')->nullable();
                $table->text('notes')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('contract_attachments')) {
            Schema::create('contract_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('uploaded_by_type')->nullable();
                $table->unsignedBigInteger('uploaded_by_id')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_attachments');
        Schema::dropIfExists('contract_histories');
    }
};
