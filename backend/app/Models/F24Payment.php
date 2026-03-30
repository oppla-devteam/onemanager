<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F24Payment extends Model
{
    protected $fillable = [
        'payment_date',
        'type',
        'tax_code',
        'amount',
        'period',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
