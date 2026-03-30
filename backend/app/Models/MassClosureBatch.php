<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MassClosureBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'start_date',
        'end_date',
        'reason',
        'status',
        'total_restaurants',
        'successful_closures',
        'failed_closures',
        'output',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'total_restaurants' => 'integer',
        'successful_closures' => 'integer',
        'failed_closures' => 'integer',
    ];

    /**
     * Get the holiday mappings for this batch
     */
    public function holidayMappings()
    {
        return $this->hasMany(MassClosureHolidayMapping::class, 'batch_id', 'batch_id');
    }

    /**
     * Scope to get only running batches
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope to get only completed batches
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get recent batches
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
