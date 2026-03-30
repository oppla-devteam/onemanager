<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'partner_email',
        'partner_name',
        'stripe_account_id',
        'stripe_charge_id',
        'commission_amount',
        'currency',
        'transaction_date',
        'order_id',
        'description',
        'stripe_data',
        'period_month',
        'invoiced',
        'invoice_id',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'invoiced' => 'boolean',
        'stripe_data' => 'array',
    ];

    /**
     * Relazione con Client (partner)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relazione con Invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Scope per filtrare per mese
     */
    public function scopeForPeriod($query, string $periodMonth)
    {
        return $query->where('period_month', $periodMonth);
    }

    /**
     * Scope per commissioni non fatturate
     */
    public function scopeNotInvoiced($query)
    {
        return $query->where('invoiced', false);
    }

    /**
     * Scope per partner
     */
    public function scopeForPartner($query, string $email)
    {
        return $query->where('partner_email', $email);
    }
}
