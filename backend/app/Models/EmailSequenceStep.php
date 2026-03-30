<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSequenceStep extends Model
{
    protected $fillable = [
        'email_sequence_id',
        'order',
        'delay_days',        // Delay in days after previous step (or enrollment for first step)
        'delay_hours',       // Additional delay in hours
        'subject',
        'body',              // HTML email body with placeholders
        'template_id',       // Optional: reference to email template
        'send_conditions',   // JSON: conditions to check before sending
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'delay_days' => 'integer',
        'delay_hours' => 'integer',
        'send_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function sequence()
    {
        return $this->belongsTo(EmailSequence::class, 'email_sequence_id');
    }

    public function sentEmails()
    {
        return $this->hasMany(SentEmail::class, 'sequence_step_id');
    }

    // Calculate total delay in hours
    public function getTotalDelayHoursAttribute(): int
    {
        return ($this->delay_days * 24) + ($this->delay_hours ?? 0);
    }

    // Stats for this step
    public function getStatsAttribute(): array
    {
        $emails = $this->sentEmails;
        $sent = $emails->count();
        
        return [
            'sent' => $sent,
            'delivered' => $emails->whereNotNull('delivered_at')->count(),
            'opened' => $emails->whereNotNull('opened_at')->count(),
            'clicked' => $emails->whereNotNull('clicked_at')->count(),
            'open_rate' => $sent > 0 ? round($emails->whereNotNull('opened_at')->count() / $sent * 100, 2) : 0,
            'click_rate' => $sent > 0 ? round($emails->whereNotNull('clicked_at')->count() / $sent * 100, 2) : 0,
        ];
    }
}
