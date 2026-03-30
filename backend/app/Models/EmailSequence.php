<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailSequence extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_type',      // lead_created, client_onboarded, invoice_overdue, contract_expiring, manual
        'trigger_conditions', // JSON: specific conditions for triggering
        'status',            // draft, active, paused, archived
        'target_segment',    // leads, clients, partners, all
        'created_by',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
    ];

    public function steps()
    {
        return $this->hasMany(EmailSequenceStep::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(EmailSequenceEnrollment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTrigger($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType)->active();
    }

    // Stats
    public function getStatsAttribute(): array
    {
        $enrollments = $this->enrollments;
        
        return [
            'total_enrolled' => $enrollments->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'active' => $enrollments->where('status', 'active')->count(),
            'unsubscribed' => $enrollments->where('status', 'unsubscribed')->count(),
        ];
    }
}
