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
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            
            // Firmatario
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_role')->nullable();
            
            // Ordine di firma (per firme multiple)
            $table->integer('signing_order')->default(1);
            
            // Stato della firma
            $table->enum('status', ['pending', 'invited', 'viewed', 'signed', 'declined'])->default('pending');
            
            // Token per firma
            $table->string('signature_token', 64)->unique();
            $table->timestamp('token_expires_at');
            
            // Dati firma
            $table->string('signature_type')->nullable(); // drawn, typed, otp, digital
            $table->longText('signature_data')->nullable(); // Base64 image o testo
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            // OTP
            $table->string('otp_code', 6)->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            
            // Timestamp eventi
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            
            // Motivo rifiuto
            $table->text('decline_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('signature_token');
            $table->index('status');
            $table->index(['contract_id', 'signing_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
    }
};
