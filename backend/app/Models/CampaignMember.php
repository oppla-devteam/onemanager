<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'member_type',
        'member_id',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function member()
    {
        return $this->morphTo();
    }
}
