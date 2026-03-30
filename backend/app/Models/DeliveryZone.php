<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = [
        'oppla_id',
        'name',
        'city',
        'description',
        'postal_codes',
        'price_ranges',
        'geometry',
        'center_lat',
        'center_lng',
        'color',
        'source',
        'is_active',
    ];

    protected $casts = [
        'postal_codes' => 'array',
        'price_ranges' => 'array',
        'geometry' => 'array',
        'center_lat' => 'decimal:7',
        'center_lng' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    /**
     * Check if zone has a valid polygon geometry
     */
    public function hasGeometry(): bool
    {
        return !empty($this->geometry) && 
               isset($this->geometry['type']) && 
               isset($this->geometry['coordinates']);
    }

    /**
     * Calculate center point from polygon coordinates
     */
    public function calculateCenter(): array
    {
        if (!$this->hasGeometry()) {
            return ['lat' => null, 'lng' => null];
        }

        $coordinates = $this->geometry['coordinates'][0] ?? [];
        if (empty($coordinates)) {
            return ['lat' => null, 'lng' => null];
        }

        $sumLat = 0;
        $sumLng = 0;
        $count = count($coordinates);

        foreach ($coordinates as $coord) {
            $sumLng += $coord[0];
            $sumLat += $coord[1];
        }

        return [
            'lat' => $sumLat / $count,
            'lng' => $sumLng / $count,
        ];
    }

    /**
     * Scope for zones with geometry
     */
    public function scopeWithGeometry($query)
    {
        return $query->whereNotNull('geometry');
    }

    /**
     * Scope for zones synced from Oppla
     */
    public function scopeFromOppla($query)
    {
        return $query->whereNotNull('oppla_id');
    }

    /**
     * Scope for manually created zones
     */
    public function scopeManual($query)
    {
        return $query->where('source', 'manual');
    }
}
