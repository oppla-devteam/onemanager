<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    protected $fillable = [
        'enrollment_id',
        'sequence_step_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'message_id',        // Email provider message ID
        'status',            // queued, sent, delivered, bounced, failed
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(EmailSequenceEnrollment::class, 'enrollment_id');
    }

    public function step()
    {
        return $this->belongsTo(EmailSequenceStep::class, 'sequence_step_id');
    }

    // Scopes
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    // Tracking methods
    public function markDelivered(): self
    {
        $this->update(['delivered_at' => now(), 'status' => 'delivered']);
        return $this;
    }

    public function markOpened(): self
    {
        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
        return $this;
    }

    public function markClicked(): self
    {
        // Mark opened if not already
        if (!$this->opened_at) {
            $this->opened_at = now();
        }
        if (!$this->clicked_at) {
            $this->update(['clicked_at' => now(), 'opened_at' => $this->opened_at]);
        }
        return $this;
    }

    public function markBounced(string $reason = null): self
    {
        $this->update([
            'bounced_at' => now(),
            'status' => 'bounced',
            'error_message' => $reason,
        ]);
        return $this;
    }
}
