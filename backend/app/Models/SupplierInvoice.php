<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'fic_id',
        'numero_fattura',
        'data_emissione',
        'data_scadenza',
        'data_pagamento',
        'imponibile',
        'iva',
        'totale',
        'sdi_identifier',
        'adi_file_path',
        'pdf_file_path',
        'status',
        'payment_status',
        'bank_account_id',
        'note',
    ];

    protected $casts = [
        'data_emissione' => 'date',
        'data_scadenza' => 'date',
        'data_pagamento' => 'date',
        'imponibile' => 'decimal:2',
        'iva' => 'decimal:2',
        'totale' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Alias per compatibilità con controller
    public function getInvoiceNumberAttribute(): string
    {
        return $this->numero_fattura;
    }

    public function getInvoiceDateAttribute()
    {
        return $this->data_emissione;
    }

    public function getDueDateAttribute()
    {
        return $this->data_scadenza;
    }

    public function getAmountAttribute()
    {
        return $this->imponibile;
    }

    public function getVatAmountAttribute()
    {
        return $this->iva;
    }

    public function getTotalAmountAttribute()
    {
        return $this->totale;
    }

    public function getPaidAtAttribute()
    {
        return $this->data_pagamento;
    }

    public function getFilePathAttribute()
    {
        return $this->pdf_file_path;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'non_pagata');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'pagata');
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'non_pagata')
            ->where('data_scadenza', '<', now());
    }
}
