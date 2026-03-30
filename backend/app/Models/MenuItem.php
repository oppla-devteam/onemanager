<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'category',
        'product_name',
        'description',
        'price_cents',
        'available_for_delivery',
        'available_for_pickup',
        'is_active',
        'image_url',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'available_for_delivery' => 'boolean',
        'available_for_pickup' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    // Accessors
    public function getPriceAttribute()
    {
        return $this->price_cents / 100;
    }

    public function getFormattedPriceAttribute()
    {
        return '€' . number_format($this->price_cents / 100, 2, ',', '.');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAvailableForDelivery($query)
    {
        return $query->where('available_for_delivery', true)
                     ->where('is_active', true);
    }

    public function scopeAvailableForPickup($query)
    {
        return $query->where('available_for_pickup', true)
                     ->where('is_active', true);
    }

    public function scopeByRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('sort_order')->orderBy('product_name');
    }
}
