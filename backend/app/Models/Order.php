<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'restaurant_id',
        'oppla_order_id',
        'order_number',
        'customer_name',
        'order_date',
        'subtotal',
        'delivery_fee',
        'discount',
        'total_amount',
        'currency',
        'status',
        'delivery_type',
        'items',
        'items_count',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'shipping_cap',
        'shipping_country',
        'tracking_number',
        'carrier',
        'delivery_status',
        'delivered_at',
        'invoice_id',
        'is_invoiced',
        'oppla_sync_at',
        'oppla_data',
        'tookan_job_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'oppla_sync_at' => 'datetime',
        'delivered_at' => 'datetime',
        'items' => 'array',
        'oppla_data' => 'array',
        'is_invoiced' => 'boolean',
        'subtotal' => 'integer',
        'delivery_fee' => 'integer',
        'discount' => 'integer',
        'total_amount' => 'integer',
    ];

    // Rimuoviamo gli appends perché ora usiamo i campi fisici
    // protected $appends = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
