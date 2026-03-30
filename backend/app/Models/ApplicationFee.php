<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_fee_id',
        'amount',
        'currency',
        'created_at_stripe',
        'stripe_account_id',
        'partner_email',
        'partner_name',
        'client_id',
        'charge_id',
        'description',
        'period_month',
        'raw_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at_stripe' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Relazione con il cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
