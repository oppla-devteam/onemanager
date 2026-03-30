<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'username',
        'first_name',
        'last_name',
        'email',
        'phone',
        'status',
        'status_code',
        'is_blocked',
        'transport_type',
        'transport_type_code',
        'latitude',
        'longitude',
        'team_id',
        'team_name',
        'tags',
        'profile_image',
        'fleet_last_updated_at',
        'last_synced_at',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'fleet_last_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $appends = ['name'];

    /**
     * Get the rider's full name
     */
    public function getNameAttribute(): string
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");
        return $fullName ?: $this->username ?: 'Rider';
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'available' => 'Disponibile',
            'busy' => 'In consegna',
            'offline' => 'Offline',
            default => 'Sconosciuto',
        };
    }

    /**
     * Get human-readable transport type label
     */
    public function getTransportTypeLabelAttribute(): string
    {
        return match($this->transport_type) {
            'motorcycle' => 'Moto/Scooter',
            'bicycle' => 'Bicicletta',
            'car' => 'Auto',
            'foot' => 'A piedi',
            'truck' => 'Furgone',
            default => ucfirst($this->transport_type),
        };
    }

    /**
     * Scope: Get only available riders
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope: Get only busy riders
     */
    public function scopeBusy($query)
    {
        return $query->where('status', 'busy');
    }

    /**
     * Scope: Get only offline riders
     */
    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }

    /**
     * Scope: Get riders by team
     */
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope: Get riders that haven't been synced recently
     */
    public function scopeStale($query, int $minutes = 10)
    {
        return $query->where(function($q) use ($minutes) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subMinutes($minutes));
        });
    }

    /**
     * Check if rider data is stale (older than N minutes)
     */
    public function isStale(int $minutes = 10): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->lt(now()->subMinutes($minutes));
    }

    /**
     * Get minutes since last sync
     */
    public function getMinutesSinceSync(): ?int
    {
        if (!$this->last_synced_at) {
            return null;
        }

        return $this->last_synced_at->diffInMinutes(now());
    }
}
