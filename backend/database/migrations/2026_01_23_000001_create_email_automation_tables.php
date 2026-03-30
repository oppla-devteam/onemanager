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
        // Email Sequences (drip campaigns)
        Schema::create('email_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type'); // lead_created, client_onboarded, invoice_overdue, contract_expiring, manual
            $table->json('trigger_conditions')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->string('target_segment')->nullable(); // leads, clients, partners, all
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Email Sequence Steps (individual emails in a sequence)
        Schema::create('email_sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_sequence_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(1);
            $table->integer('delay_days')->default(0);
            $table->integer('delay_hours')->default(0);
            $table->string('subject');
            $table->longText('body');
            $table->foreignId('template_id')->nullable();
            $table->json('send_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['email_sequence_id', 'order']);
        });

        // Email Sequence Enrollments (tracking who is in which sequence)
        Schema::create('email_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_sequence_id')->constrained()->onDelete('cascade');
            $table->morphs('enrollable'); // Lead, Client, etc.
            $table->enum('status', ['active', 'completed', 'paused', 'unsubscribed', 'failed'])->default('active');
            $table->integer('current_step')->default(1);
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'next_send_at']);
            $table->index(['email_sequence_id', 'status']);
        });

        // Sent Emails (tracking individual sent emails)
        Schema::create('sent_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('email_sequence_enrollments')->onDelete('cascade');
            $table->foreignId('sequence_step_id')->nullable()->constrained('email_sequence_steps')->onDelete('set null');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->longText('body');
            $table->string('message_id')->nullable(); // Email provider message ID
            $table->enum('status', ['queued', 'sent', 'delivered', 'bounced', 'failed'])->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'sent_at']);
            $table->index('recipient_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_emails');
        Schema::dropIfExists('email_sequence_enrollments');
        Schema::dropIfExists('email_sequence_steps');
        Schema::dropIfExists('email_sequences');
    }
};
