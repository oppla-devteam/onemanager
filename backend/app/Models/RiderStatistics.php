<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RiderStatistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_time',
        'data_source',
        'total_riders',
        'available_riders',
        'busy_riders',
        'offline_riders',
    ];

    protected $casts = [
        'snapshot_time' => 'datetime',
        'total_riders' => 'integer',
        'available_riders' => 'integer',
        'busy_riders' => 'integer',
        'offline_riders' => 'integer',
    ];

    /**
     * Scope: Get today's snapshots
     */
    public function scopeToday($query)
    {
        return $query->whereDate('snapshot_time', Carbon::today());
    }

    /**
     * Scope: Get snapshots from the last N hours
     */
    public function scopeLastHours($query, int $hours = 24)
    {
        return $query->where('snapshot_time', '>=', now()->subHours($hours))
                    ->orderBy('snapshot_time', 'asc');
    }

    /**
     * Scope: Get snapshots from the last N days
     */
    public function scopeLastDays($query, int $days = 7)
    {
        return $query->where('snapshot_time', '>=', now()->subDays($days))
                    ->orderBy('snapshot_time', 'asc');
    }

    /**
     * Scope: Filter by data source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('data_source', $source);
    }

    /**
     * Scope: Get hourly aggregates for a date range
     */
    public function scopeHourlyAggregates($query, ?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $startDate = $startDate ?? now()->subDays(7);
        $endDate = $endDate ?? now();

        return $query->whereBetween('snapshot_time', [$startDate, $endDate])
                    ->selectRaw('
                        DATE_FORMAT(snapshot_time, "%Y-%m-%d %H:00:00") as hour,
                        AVG(total_riders) as avg_total,
                        AVG(available_riders) as avg_available,
                        AVG(busy_riders) as avg_busy,
                        AVG(offline_riders) as avg_offline,
                        COUNT(*) as snapshot_count
                    ')
                    ->groupBy('hour')
                    ->orderBy('hour', 'asc');
    }

    /**
     * Get availability percentage
     */
    public function getAvailabilityPercentageAttribute(): float
    {
        if ($this->total_riders === 0) {
            return 0;
        }

        return round(($this->available_riders / $this->total_riders) * 100, 2);
    }

    /**
     * Get busy percentage
     */
    public function getBusyPercentageAttribute(): float
    {
        if ($this->total_riders === 0) {
            return 0;
        }

        return round(($this->busy_riders / $this->total_riders) * 100, 2);
    }
}
