<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MassClosureHolidayMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'oppla_holiday_id',
        'oppla_restaurant_id',
        'restaurant_name',
    ];

    protected $casts = [
        'oppla_holiday_id' => 'string',
        'oppla_restaurant_id' => 'string',
    ];

    /**
     * Get the batch this mapping belongs to
     */
    public function batch()
    {
        return $this->belongsTo(MassClosureBatch::class, 'batch_id', 'batch_id');
    }
}
