<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerPenalty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_penalties';

    const TYPE_DELAY_THRESHOLD = 'delay_threshold';
    const TYPE_FORGOTTEN_ITEM = 'forgotten_item';
    const TYPE_BULKY_UNMARKED = 'bulky_unmarked';
    const TYPE_BULKY_REPEATED = 'bulky_repeated';
    const TYPE_DOUBLE_DELIVERY = 'double_delivery';
    const TYPE_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_INVOICED = 'invoiced';
    const STATUS_PAID = 'paid';
    const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'restaurant_id',
        'client_id',
        'penalty_type',
        'amount',
        'currency',
        'billing_status',
        'invoice_id',
        'description',
        'incident_ids',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'incident_ids' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function incidents()
    {
        return $this->hasMany(PartnerIncident::class, 'penalty_id');
    }

    public function scopePending($query)
    {
        return $query->where('billing_status', self::STATUS_PENDING);
    }

    public function scopeInvoiced($query)
    {
        return $query->where('billing_status', self::STATUS_INVOICED);
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function isPending(): bool
    {
        return $this->billing_status === self::STATUS_PENDING;
    }

    public function isInvoiced(): bool
    {
        return $this->billing_status === self::STATUS_INVOICED;
    }

    public function markAsInvoiced(int $invoiceId): void
    {
        $this->update([
            'billing_status' => self::STATUS_INVOICED,
            'invoice_id' => $invoiceId,
        ]);
    }

    public function waive(): void
    {
        $this->update(['billing_status' => self::STATUS_WAIVED]);
    }

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_DELAY_THRESHOLD => 'Superamento soglia ritardi',
            self::TYPE_FORGOTTEN_ITEM => 'Dimenticanza prodotto',
            self::TYPE_BULKY_UNMARKED => 'Voluminoso non segnalato',
            self::TYPE_BULKY_REPEATED => 'Voluminosi non segnalati ripetuti',
            self::TYPE_DOUBLE_DELIVERY => 'Doppia consegna',
            self::TYPE_OTHER => 'Altro',
            default => $type,
        };
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Da fatturare',
            self::STATUS_INVOICED => 'Fatturato',
            self::STATUS_PAID => 'Pagato',
            self::STATUS_WAIVED => 'Annullato',
            default => $status,
        };
    }
}
