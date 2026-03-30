<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'ragione_sociale',
        'piva',
        'codice_fiscale',
        'email',
        'phone',
        'pec',
        'sdi_code',
        'indirizzo',
        'citta',
        'provincia',
        'cap',
        'nazione',
        'type',
        'iban',
        'giorni_pagamento',
        'is_active',
        'note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'giorni_pagamento' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Alias for compatibility
    public function getPaymentTermsAttribute(): int
    {
        return $this->giorni_pagamento ?? 30;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->note;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ragione_sociale', 'piva', 'email', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeItalian($query)
    {
        return $query->where('type', 'italiano_sdi');
    }

    public function scopeForeign($query)
    {
        return $query->where('type', 'estero');
    }
}
