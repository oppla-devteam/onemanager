<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'subject',
        'description',
        'status',
        'related_type',
        'related_id',
        'due_date',
        'completed_at',
        'duration_minutes',
        'assigned_to',
        'created_by',
        'email_from',
        'email_to',
        'email_cc',
        'attachments',
        'call_direction',
        'call_outcome',
        'priority',
        'reminder_at',
        'reminder_sent',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'attachments' => 'array',
        'duration_minutes' => 'integer',
    ];

    public function related()
    {
        return $this->morphTo();
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->creator(); // Alias
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeDueToday($query)
    {
        return $query->where('status', 'pending')
            ->whereDate('due_date', today());
    }

    public function scopeNeedReminder($query)
    {
        return $query->where('status', 'pending')
            ->where('reminder_sent', false)
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now());
    }
}
