<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Delivery extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'client_id',
        'order_id',
        'order_type',
        'is_partner_logistico',
        'pickup_address',
        'delivery_address',
        'distance_km',
        'order_amount',
        'delivery_fee_base',
        'delivery_fee_distance',
        'delivery_fee_total',
        'oppla_fee',
        'order_date',
        'pickup_time',
        'delivery_time',
        'status',
        'rider_id',
        'invoice_id',
        'is_invoiced',
        'note',
        // Campi aggiuntivi da OPPLA managed_deliveries
        'oppla_id',
        'partner_id',
        'restaurant_id',
        'user_id',
        'delivery_code',
        'delivery_scheduled_at',
        'shipping_address',
        'gps_location',
        'delivery_notes',
        'customer_name',
        'customer_phone',
        'original_amount',
        'payment_method',
        'platform_fee',
        'distance_fee',
        'platform_fee_id',
        'distance_fee_id',
        'payment_intent',
        'oppla_created_at',
        'oppla_updated_at',
        'tookan_job_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'pickup_time' => 'datetime',
        'delivery_time' => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'distance_km' => 'decimal:2',
        'order_amount' => 'decimal:2',
        'delivery_fee_base' => 'decimal:2',
        'delivery_fee_distance' => 'decimal:2',
        'delivery_fee_total' => 'decimal:2',
        'oppla_fee' => 'decimal:2',
        'is_partner_logistico' => 'boolean',
        'is_invoiced' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'oppla_created_at' => 'datetime',
        'oppla_updated_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'rider_name', 'delivered_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['in_attesa', 'assegnata', 'in_consegna']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completata');
    }
}
