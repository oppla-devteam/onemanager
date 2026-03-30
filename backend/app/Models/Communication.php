<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [
        'communicable_type',
        'communicable_id',
        'content',
        'type',
        'is_pinned',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'metadata' => 'array',
    ];

    public function communicable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
