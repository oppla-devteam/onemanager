<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pipeline Stages (personalizzabili)
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'Lead', 'Qualificato', 'Proposta', 'Negoziazione', 'Chiuso Vinto', 'Chiuso Perso'
            $table->string('type'); // 'lead', 'opportunity', 'customer', 'lost'
            $table->integer('order')->default(0);
            $table->string('color')->default('#6366f1');
            $table->decimal('win_probability', 5, 2)->default(0); // 0-100
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Leads (potenziali clienti)
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_number')->unique(); // LEAD-2025-0001
            
            // Dati base
            $table->string('company_name')->nullable();
            $table->string('contact_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            
            // Indirizzo
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('IT');
            
            // Classificazione
            $table->foreignId('pipeline_stage_id')->constrained('pipeline_stages');
            $table->enum('source', [
                'website', 'referral', 'social_media', 'email_campaign', 
                'cold_call', 'event', 'partner', 'other'
            ])->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('rating', ['hot', 'warm', 'cold'])->nullable();
            
            // Business
            $table->string('industry')->nullable(); // 'restaurant', 'retail', 'service', ...
            $table->integer('company_size')->nullable(); // numero dipendenti
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->date('estimated_close_date')->nullable();
            
            // Assegnazione e tracking
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            
            // Conversione
            $table->foreignId('converted_to_client_id')->nullable()->constrained('clients');
            $table->unsignedBigInteger('converted_to_opportunity_id')->nullable();
            $table->datetime('converted_at')->nullable();
            
            // Date importanti
            $table->datetime('last_contact_at')->nullable();
            $table->datetime('next_follow_up_at')->nullable();
            $table->datetime('lost_at')->nullable();
            $table->string('lost_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('phone');
            $table->index(['pipeline_stage_id', 'assigned_to']);
        });

        // Opportunities (trattative in corso)
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('opportunity_number')->unique(); // OPP-2025-0001
            
            // Relazioni
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            
            // Dettagli
            $table->string('name'); // Nome opportunità
            $table->text('description')->nullable();
            $table->foreignId('pipeline_stage_id')->constrained('pipeline_stages');
            
            // Valore
            $table->decimal('amount', 12, 2); // Valore stimato
            $table->integer('win_probability')->default(50); // 0-100
            $table->decimal('weighted_amount', 12, 2)->nullable(); // amount * probability
            
            // Timeline
            $table->date('expected_close_date');
            $table->datetime('closed_at')->nullable();
            $table->enum('status', ['open', 'won', 'lost'])->default('open');
            $table->text('close_notes')->nullable();
            
            // Tracking
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('source')->nullable();
            $table->integer('days_in_stage')->default(0);
            
            // Risultato
            $table->string('lost_reason')->nullable();
            $table->foreignId('competitor_id')->nullable(); // Chi ha vinto se perso
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['pipeline_stage_id', 'status']);
            $table->index('expected_close_date');
        });

        // Attività (chiamate, email, meeting, task)
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            
            // Tipo e dati
            $table->enum('type', ['call', 'email', 'meeting', 'task', 'note', 'sms']);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            
            // Relazioni (polymorphic - può essere collegato a lead, client, opportunity, contract, etc.)
            $table->morphs('related'); // related_type, related_id
            
            // Timing
            $table->datetime('due_date')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // durata chiamata/meeting
            
            // Assegnazione
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            
            // Specifico per email
            $table->string('email_from')->nullable();
            $table->string('email_to')->nullable();
            $table->string('email_cc')->nullable();
            $table->json('attachments')->nullable();
            
            // Specifico per chiamate
            $table->enum('call_direction', ['inbound', 'outbound'])->nullable();
            $table->enum('call_outcome', ['answered', 'voicemail', 'busy', 'no_answer'])->nullable();
            
            // Priorità e reminder
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->datetime('reminder_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            
            $table->timestamps();
            
            // morphs('related') già crea l'indice ['related_type', 'related_id']
            $table->index(['assigned_to', 'status', 'due_date']);
        });

        // Note/Comunicazioni
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            
            // Relazioni polymorphic
            $table->morphs('communicable'); // lead, client, opportunity, contract
            
            // Contenuto
            $table->text('content');
            $table->enum('type', ['note', 'email', 'call', 'meeting', 'sms', 'whatsapp'])->default('note');
            $table->boolean('is_pinned')->default(false);
            
            // Metadata
            $table->foreignId('user_id')->nullable()->constrained('users'); // Chi ha creato
            $table->json('metadata')->nullable(); // Dati extra (es: email headers)
            
            $table->timestamps();
            
            // morphs('communicable') già crea l'indice
            $table->index('created_at');
        });

        // Tags (per segmentazione)
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color')->default('#6366f1');
            $table->string('category')->nullable(); // 'industry', 'size', 'status', 'campaign'
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Taggables (pivot polymorphic)
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->morphs('taggable'); // client, lead, opportunity
            $table->timestamps();
            
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });

        // Campagne Marketing
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            
            $table->enum('type', ['email', 'sms', 'social', 'event', 'webinar', 'other']);
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'completed'])->default('draft');
            
            // Targeting
            $table->json('target_segments')->nullable(); // Criteri segmentazione
            $table->integer('target_count')->default(0);
            
            // Budget e ROI
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->decimal('revenue_generated', 10, 2)->default(0);
            
            // Metriche
            $table->integer('sent_count')->default(0);
            $table->integer('delivered_count')->default(0);
            $table->integer('opened_count')->default(0);
            $table->integer('clicked_count')->default(0);
            $table->integer('converted_count')->default(0);
            
            // Timeline
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Campaign Members (chi è nella campagna)
        Schema::create('campaign_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            
            // Polymorphic - può essere lead o client
            $table->morphs('member'); // member_type, member_id
            
            $table->enum('status', ['pending', 'sent', 'delivered', 'opened', 'clicked', 'converted', 'bounced', 'unsubscribed'])->default('pending');
            $table->datetime('sent_at')->nullable();
            $table->datetime('opened_at')->nullable();
            $table->datetime('clicked_at')->nullable();
            $table->datetime('converted_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
        });

        // Aggiungi campi CRM a clients esistente
        if (!Schema::hasColumn('clients', 'client_type')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->enum('client_type', ['lead', 'prospect', 'customer', 'partner', 'former'])->default('customer')->after('id');
                $table->foreignId('lead_id')->nullable()->constrained('leads')->after('client_type');
                // status già esiste nella tabella clients
                
                // Customer Success
                $table->enum('health_score', ['excellent', 'good', 'at_risk', 'critical'])->nullable();
                $table->integer('satisfaction_score')->nullable(); // NPS 0-10
                $table->date('onboarding_completed_at')->nullable();
                $table->date('last_order_at')->nullable();
                $table->integer('orders_count')->default(0);
                $table->decimal('lifetime_value', 12, 2)->default(0);
                $table->decimal('average_order_value', 10, 2)->nullable();
                
                // Segmentazione
                $table->string('segment')->nullable(); // 'enterprise', 'smb', 'startup'
                $table->string('industry')->nullable();
                $table->integer('company_size')->nullable();
                
                // Assegnazione
                $table->foreignId('account_manager_id')->nullable()->constrained('users');
                
                // Social
                $table->string('linkedin_url')->nullable();
                $table->string('facebook_url')->nullable();
                $table->string('instagram_url')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_members');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('communications');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('pipeline_stages');
        
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'client_type', 'lead_id', 'status', 'health_score', 'satisfaction_score',
                'onboarding_completed_at', 'last_order_at', 'orders_count', 'lifetime_value',
                'average_order_value', 'segment', 'industry', 'company_size',
                'account_manager_id', 'linkedin_url', 'facebook_url', 'instagram_url'
            ]);
        });
    }
};
