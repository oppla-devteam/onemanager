<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeClass extends Model
{
    protected $fillable = [
        'name',
        'description',
        'delivery_type',
        'best_price',
        'monthly_fee',
        'order_fee_percentage',
        'order_fee_fixed',
        'delivery_base_fee',
        'delivery_km_fee',
        'payment_processing_fee',
        'platform_fee',
        'is_active',
    ];

    protected $casts = [
        'best_price' => 'boolean',
        'is_active' => 'boolean',
        'monthly_fee' => 'decimal:2',
        'order_fee_percentage' => 'decimal:2',
        'order_fee_fixed' => 'decimal:2',
        'delivery_base_fee' => 'decimal:2',
        'delivery_km_fee' => 'decimal:2',
        'payment_processing_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
    ];

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }
}
