<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'oppla_external_id',
        'nome',
        'cognome',
        'email',
        'telefono',
        'restaurant_id',
        'user_id',
        'is_active',
        'oppla_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'oppla_sync_at' => 'datetime',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
