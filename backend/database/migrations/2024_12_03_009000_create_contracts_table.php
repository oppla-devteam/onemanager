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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->string('contract_type')->default('servizio'); // servizio, fornitura, partnership, lavoro, altro
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('duration_months')->nullable();
            $table->boolean('auto_renew')->default(false);
            
            $table->decimal('value', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->enum('billing_frequency', ['monthly', 'quarterly', 'yearly', 'one_time'])->default('monthly');
            
            // File contratto
            $table->string('file_path')->nullable();
            
            // Stato (mantengo sia 'draft' che 'bozza' per compatibilità)
            $table->string('status')->default('bozza'); // bozza, attivo, in_scadenza, scaduto, sospeso, terminato
            $table->foreignId('signed_by')->nullable()->constrained('users');
            $table->timestamp('signed_at')->nullable();
            
            // Notifiche scadenza
            $table->boolean('notify_expiration')->default(true);
            $table->integer('notify_days_before')->default(30);
            
            $table->text('note')->nullable();
            $table->json('terms')->nullable(); // Termini contrattuali (partner info, servizi, etc)
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['client_id', 'status']);
            $table->index(['end_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
