<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'bank_statement_id',
        'source',
        'source_transaction_id',
        'transaction_date',
        'value_date',
        'type',
        'amount',
        'fee',
        'net_amount',
        'gross_amount',
        'currency',
        'descrizione',
        'causale',
        'beneficiario',
        'normalized_beneficiary',
        'reference',
        'balance_after',
        'category',
        'category_id',
        'is_reconciled',
        'invoice_id',
        'supplier_invoice_id',
        'client_id',
        'note',
        'source_data',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status', 'transaction_id', 'display_type', 'description'];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountingCategory::class, 'category_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function scopeEntrate($query)
    {
        return $query->where('type', 'entrata');
    }

    public function scopeUscite($query)
    {
        return $query->where('type', 'uscita');
    }

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Computed attribute: Map Italian types to English for frontend
     */
    public function getDisplayTypeAttribute()
    {
        $dbType = $this->attributes['type'] ?? null;
        return match($dbType) {
            'entrata' => 'income',
            'uscita' => 'expense',
            'bonifico' => 'withdrawal',
            'addebito' => 'fee',
            'carta' => 'expense',
            'altro' => 'expense',
            default => $dbType,
        };
    }

    /**
     * Computed attribute: sempre 'completed' per transazioni esistenti
     */
    public function getStatusAttribute()
    {
        return 'completed';
    }

    /**
     * Computed attribute: alias per source_transaction_id o id
     */
    public function getTransactionIdAttribute()
    {
        return $this->attributes['source_transaction_id'] ?? 'TXN-' . $this->id;
    }

    /**
     * Computed attribute: alias per descrizione
     */
    public function getDescriptionAttribute()
    {
        return $this->attributes['descrizione'] ?? '';
    }
}
