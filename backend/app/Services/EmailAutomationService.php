<?php

namespace App\Services;

use App\Models\EmailSequence;
use App\Models\EmailSequenceEnrollment;
use App\Models\EmailSequenceStep;
use App\Models\SentEmail;
use App\Models\Lead;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;

class EmailAutomationService
{
    /**
     * Enroll an entity (Lead/Client) in an email sequence
     */
    public function enroll(Model $entity, EmailSequence $sequence, array $metadata = []): ?EmailSequenceEnrollment
    {
        // Check if already enrolled in this sequence
        $existingEnrollment = EmailSequenceEnrollment::where('email_sequence_id', $sequence->id)
            ->where('enrollable_type', get_class($entity))
            ->where('enrollable_id', $entity->id)
            ->whereIn('status', ['active', 'paused'])
            ->first();

        if ($existingEnrollment) {
            Log::info('[EmailAutomation] Entity already enrolled', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'sequence_id' => $sequence->id,
            ]);
            return $existingEnrollment;
        }

        // Get first step
        $firstStep = $sequence->steps()->where('order', 1)->first();
        
        if (!$firstStep) {
            Log::warning('[EmailAutomation] Sequence has no steps', ['sequence_id' => $sequence->id]);
            return null;
        }

        // Create enrollment
        $enrollment = EmailSequenceEnrollment::create([
            'email_sequence_id' => $sequence->id,
            'enrollable_type' => get_class($entity),
            'enrollable_id' => $entity->id,
            'status' => 'active',
            'current_step' => 1,
            'next_send_at' => now()->addHours($firstStep->total_delay_hours),
            'metadata' => $metadata,
        ]);

        Log::info('[EmailAutomation] Entity enrolled in sequence', [
            'enrollment_id' => $enrollment->id,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->id,
            'sequence' => $sequence->name,
            'first_send_at' => $enrollment->next_send_at,
        ]);

        return $enrollment;
    }

    /**
     * Process all due emails (called by scheduler)
     */
    public function processDueEmails(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $dueEnrollments = EmailSequenceEnrollment::dueToSend()
            ->with(['sequence', 'enrollable'])
            ->get();

        foreach ($dueEnrollments as $enrollment) {
            $results['processed']++;
            
            try {
                $result = $this->processEnrollment($enrollment);
                
                if ($result === 'sent') {
                    $results['sent']++;
                } elseif ($result === 'skipped') {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('[EmailAutomation] Failed to process enrollment', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[EmailAutomation] Batch processing complete', $results);
        return $results;
    }

    /**
     * Process a single enrollment
     */
    private function processEnrollment(EmailSequenceEnrollment $enrollment): string
    {
        $sequence = $enrollment->sequence;
        $entity = $enrollment->enrollable;
        
        if (!$entity) {
            $enrollment->update(['status' => 'failed']);
            return 'failed';
        }

        // Get current step
        $step = $sequence->steps()->where('order', $enrollment->current_step)->first();
        
        if (!$step || !$step->is_active) {
            // Try to advance to next step
            $enrollment->advanceToNextStep();
            return 'skipped';
        }

        // Check send conditions
        if (!$this->checkSendConditions($step, $entity)) {
            $enrollment->advanceToNextStep();
            return 'skipped';
        }

        // Get email address
        $email = $this->getEntityEmail($entity);
        
        if (!$email) {
            Log::warning('[EmailAutomation] No email for entity', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
            ]);
            $enrollment->update(['status' => 'failed']);
            return 'failed';
        }

        // Send email
        $sent = $this->sendEmail($enrollment, $step, $entity, $email);
        
        if ($sent) {
            $enrollment->advanceToNextStep();
            return 'sent';
        }

        return 'failed';
    }

    /**
     * Send an email for a sequence step
     */
    private function sendEmail(
        EmailSequenceEnrollment $enrollment,
        EmailSequenceStep $step,
        Model $entity,
        string $recipientEmail
    ): bool {
        // Parse template with placeholders
        $subject = $this->parsePlaceholders($step->subject, $entity, $enrollment);
        $body = $this->parsePlaceholders($step->body, $entity, $enrollment);
        $recipientName = $this->getEntityName($entity);

        // Create sent email record
        $sentEmail = SentEmail::create([
            'enrollment_id' => $enrollment->id,
            'sequence_step_id' => $step->id,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'body' => $body,
            'status' => 'queued',
        ]);

        try {
            // Send via Laravel Mail
            Mail::html($body, function ($message) use ($recipientEmail, $recipientName, $subject) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $sentEmail->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info('[EmailAutomation] Email sent', [
                'sent_email_id' => $sentEmail->id,
                'recipient' => $recipientEmail,
                'subject' => $subject,
            ]);

            return true;

        } catch (\Exception $e) {
            $sentEmail->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('[EmailAutomation] Email send failed', [
                'sent_email_id' => $sentEmail->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Parse placeholders in email content
     */
    private function parsePlaceholders(string $content, Model $entity, EmailSequenceEnrollment $enrollment): string
    {
        $placeholders = [
            '{{nome}}' => $this->getEntityName($entity),
            '{{email}}' => $this->getEntityEmail($entity),
            '{{azienda}}' => $this->getEntityCompany($entity),
            '{{data_oggi}}' => now()->format('d/m/Y'),
            '{{unsubscribe_link}}' => route('email.unsubscribe', ['token' => $enrollment->id]),
        ];

        // Add entity-specific placeholders
        if ($entity instanceof Lead) {
            $placeholders['{{fonte}}'] = $entity->source ?? '';
            $placeholders['{{interesse}}'] = $entity->interest_level ?? '';
        }

        if ($entity instanceof Client) {
            $placeholders['{{tipo_cliente}}'] = $entity->type ?? '';
            $placeholders['{{piva}}'] = $entity->piva ?? '';
        }

        // Add metadata placeholders
        foreach ($enrollment->metadata ?? [] as $key => $value) {
            $placeholders["{{{$key}}}"] = $value;
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Check if send conditions are met
     */
    private function checkSendConditions(EmailSequenceStep $step, Model $entity): bool
    {
        $conditions = $step->send_conditions;
        
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $field => $expectedValue) {
            $actualValue = $entity->{$field} ?? null;
            
            if ($actualValue !== $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trigger sequences based on event
     */
    public function triggerSequences(string $triggerType, Model $entity, array $metadata = []): int
    {
        $sequences = EmailSequence::forTrigger($triggerType)->get();
        $enrolled = 0;

        foreach ($sequences as $sequence) {
            // Check if entity matches target segment
            if ($this->entityMatchesSegment($entity, $sequence->target_segment)) {
                $enrollment = $this->enroll($entity, $sequence, $metadata);
                if ($enrollment) {
                    $enrolled++;
                }
            }
        }

        return $enrolled;
    }

    /**
     * Check if entity matches sequence target segment
     */
    private function entityMatchesSegment(Model $entity, ?string $segment): bool
    {
        if (!$segment || $segment === 'all') {
            return true;
        }

        return match($segment) {
            'leads' => $entity instanceof Lead,
            'clients' => $entity instanceof Client,
            'partners' => $entity instanceof Client && $entity->type === 'partner',
            default => true,
        };
    }

    /**
     * Helper: get entity email
     */
    private function getEntityEmail(Model $entity): ?string
    {
        return $entity->email ?? $entity->pec ?? null;
    }

    /**
     * Helper: get entity name
     */
    private function getEntityName(Model $entity): string
    {
        if ($entity instanceof Lead) {
            return $entity->contact_name ?? $entity->company_name ?? 'Cliente';
        }
        
        if ($entity instanceof Client) {
            return $entity->ragione_sociale ?? 'Cliente';
        }

        return $entity->name ?? 'Cliente';
    }

    /**
     * Helper: get entity company
     */
    private function getEntityCompany(Model $entity): string
    {
        if ($entity instanceof Lead) {
            return $entity->company_name ?? '';
        }
        
        if ($entity instanceof Client) {
            return $entity->ragione_sociale ?? '';
        }

        return '';
    }

    /**
     * Get sequence stats
     */
    public function getSequenceStats(EmailSequence $sequence): array
    {
        $enrollments = $sequence->enrollments;
        $sentEmails = SentEmail::whereHas('enrollment', function ($q) use ($sequence) {
            $q->where('email_sequence_id', $sequence->id);
        })->get();

        return [
            'total_enrolled' => $enrollments->count(),
            'active' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'unsubscribed' => $enrollments->where('status', 'unsubscribed')->count(),
            'emails_sent' => $sentEmails->count(),
            'emails_delivered' => $sentEmails->whereNotNull('delivered_at')->count(),
            'emails_opened' => $sentEmails->whereNotNull('opened_at')->count(),
            'emails_clicked' => $sentEmails->whereNotNull('clicked_at')->count(),
            'open_rate' => $sentEmails->count() > 0 
                ? round($sentEmails->whereNotNull('opened_at')->count() / $sentEmails->count() * 100, 2) 
                : 0,
            'click_rate' => $sentEmails->count() > 0 
                ? round($sentEmails->whereNotNull('clicked_at')->count() / $sentEmails->count() * 100, 2) 
                : 0,
        ];
    }
}
