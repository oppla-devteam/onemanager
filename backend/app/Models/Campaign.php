<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'status',
        'target_segments',
        'target_count',
        'budget',
        'actual_cost',
        'revenue_generated',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'converted_count',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'target_segments' => 'array',
        'target_count' => 'integer',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'revenue_generated' => 'decimal:2',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'converted_count' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(CampaignMember::class);
    }

    public function getOpenRateAttribute(): float
    {
        return $this->delivered_count > 0 
            ? ($this->opened_count / $this->delivered_count) * 100 
            : 0;
    }

    public function getClickRateAttribute(): float
    {
        return $this->opened_count > 0 
            ? ($this->clicked_count / $this->opened_count) * 100 
            : 0;
    }

    public function getConversionRateAttribute(): float
    {
        return $this->sent_count > 0 
            ? ($this->converted_count / $this->sent_count) * 100 
            : 0;
    }

    public function getRoiAttribute(): float
    {
        return $this->actual_cost > 0 
            ? (($this->revenue_generated - $this->actual_cost) / $this->actual_cost) * 100 
            : 0;
    }
}
