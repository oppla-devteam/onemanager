<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_number',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'mobile',
        'website',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'pipeline_stage_id',
        'status',
        'source',
        'priority',
        'rating',
        'industry',
        'company_size',
        'estimated_value',
        'estimated_close_date',
        'assigned_to',
        'notes',
        'custom_fields',
        'converted_to_client_id',
        'converted_to_opportunity_id',
        'converted_at',
        'last_contact_at',
        'next_follow_up_at',
        'lost_at',
        'lost_reason',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'estimated_value' => 'decimal:2',
        'estimated_close_date' => 'date',
        'converted_at' => 'datetime',
        'last_contact_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'lost_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($lead) {
            if (empty($lead->lead_number)) {
                $lead->lead_number = self::generateLeadNumber();
            }
        });
    }

    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUser()
    {
        return $this->assignedTo(); // Alias
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'converted_to_client_id');
    }

    public function convertedClient()
    {
        return $this->client(); // Alias
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class, 'converted_to_opportunity_id');
    }

    public function convertedOpportunity()
    {
        return $this->opportunity(); // Alias
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

    public static function generateLeadNumber(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $number = $last ? (int)substr($last->lead_number, -4) + 1 : 1;
        return sprintf('LEAD-%s-%04d', $year, $number);
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    public function isQualified(): bool
    {
        return $this->pipelineStage && $this->pipelineStage->type !== 'lead';
    }

    public function getDaysInStageAttribute(): int
    {
        return $this->updated_at->diffInDays(now());
    }

    public function scopeActive($query)
    {
        return $query->whereNull('converted_at')->whereNull('lost_at');
    }

    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }

    public function scopeLost($query)
    {
        return $query->whereNotNull('lost_at');
    }

    public function scopeNeedFollowUp($query)
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now())
            ->active();
    }

    /**
     * Add an activity to this lead
     */
    public function addActivity(string $type, string $subject, array $data = []): Activity
    {
        return $this->activities()->create(array_merge([
            'type' => $type,
            'subject' => $subject,
            'created_by' => auth()->id(),
        ], $data));
    }
}
