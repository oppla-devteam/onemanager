<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'opportunity_number',
        'client_id',
        'lead_id',
        'name',
        'description',
        'pipeline_stage_id',
        'amount',
        'win_probability',
        'weighted_amount',
        'expected_close_date',
        'closed_at',
        'status',
        'close_notes',
        'assigned_to',
        'source',
        'days_in_stage',
        'lost_reason',
        'competitor_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'weighted_amount' => 'decimal:2',
        'win_probability' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
        'days_in_stage' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($opportunity) {
            if (empty($opportunity->opportunity_number)) {
                $opportunity->opportunity_number = self::generateOpportunityNumber();
            }
        });

        static::saving(function ($opportunity) {
            // Calcola weighted amount
            $opportunity->weighted_amount = ($opportunity->amount * $opportunity->win_probability) / 100;
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'related');
    }

    public function communications()
    {
        return $this->morphMany(Communication::class, 'communicable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public static function generateOpportunityNumber(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $number = $last ? (int)substr($last->opportunity_number, -4) + 1 : 1;
        return sprintf('OPP-%s-%04d', $year, $number);
    }

    public function markAsWon(string $notes = null): void
    {
        $this->update([
            'status' => 'won',
            'closed_at' => now(),
            'close_notes' => $notes,
        ]);
    }

    public function markAsLost(string $reason, string $notes = null): void
    {
        $this->update([
            'status' => 'lost',
            'closed_at' => now(),
            'lost_reason' => $reason,
            'close_notes' => $notes,
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeClosingSoon($query, int $days = 7)
    {
        return $query->where('status', 'open')
            ->whereDate('expected_close_date', '>=', now())
            ->whereDate('expected_close_date', '<=', now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'open')
            ->whereDate('expected_close_date', '<', now());
    }
}
