<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSequenceEnrollment extends Model
{
    protected $fillable = [
        'email_sequence_id',
        'enrollable_type',   // Lead, Client, etc.
        'enrollable_id',
        'status',            // active, completed, paused, unsubscribed, failed
        'current_step',      // Current step order number
        'next_send_at',      // When next email should be sent
        'completed_at',
        'unsubscribed_at',
        'metadata',          // JSON: additional data for personalization
    ];

    protected $casts = [
        'current_step' => 'integer',
        'next_send_at' => 'datetime',
        'completed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function sequence()
    {
        return $this->belongsTo(EmailSequence::class, 'email_sequence_id');
    }

    public function enrollable()
    {
        return $this->morphTo();
    }

    public function sentEmails()
    {
        return $this->hasMany(SentEmail::class, 'enrollment_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueToSend($query)
    {
        return $query->active()
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', now());
    }

    // Methods
    public function pause(): self
    {
        $this->update(['status' => 'paused']);
        return $this;
    }

    public function resume(): self
    {
        $this->update(['status' => 'active']);
        return $this;
    }

    public function unsubscribe(): self
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
        return $this;
    }

    public function complete(): self
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        return $this;
    }

    public function advanceToNextStep(): void
    {
        $nextStepOrder = $this->current_step + 1;
        $nextStep = $this->sequence->steps()->where('order', $nextStepOrder)->first();

        if ($nextStep) {
            $this->update([
                'current_step' => $nextStepOrder,
                'next_send_at' => now()->addHours($nextStep->total_delay_hours),
            ]);
        } else {
            $this->complete();
        }
    }
}
