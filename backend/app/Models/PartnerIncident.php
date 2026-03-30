<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerIncident extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_incidents';

    const TYPE_DELAY = 'delay';
    const TYPE_FORGOTTEN_ITEM = 'forgotten_item';
    const TYPE_BULKY_UNMARKED = 'bulky_unmarked';
    const TYPE_PACKAGING_ISSUE = 'packaging_issue';
    const TYPE_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'restaurant_id',
        'delivery_id',
        'rider_fleet_id',
        'reported_by_user_id',
        'incident_type',
        'delay_minutes',
        'description',
        'metadata',
        'status',
        'resolution_notes',
        'resolved_by_user_id',
        'resolved_at',
        'penalty_id',
    ];

    protected $casts = [
        'delay_minutes' => 'integer',
        'metadata' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function penalty()
    {
        return $this->belongsTo(PartnerPenalty::class, 'penalty_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('incident_type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_DELAY => 'Ritardo consegna',
            self::TYPE_FORGOTTEN_ITEM => 'Prodotto dimenticato',
            self::TYPE_BULKY_UNMARKED => 'Ordine voluminoso non segnalato',
            self::TYPE_PACKAGING_ISSUE => 'Problema packaging',
            self::TYPE_OTHER => 'Altro',
            default => $type,
        };
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'In attesa',
            self::STATUS_REVIEWED => 'In revisione',
            self::STATUS_RESOLVED => 'Risolto',
            self::STATUS_DISPUTED => 'Contestato',
            default => $status,
        };
    }
}
