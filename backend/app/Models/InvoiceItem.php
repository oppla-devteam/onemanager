<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'descrizione',
        'quantita',
        'prezzo_unitario',
        'sconto',
        'iva_percentuale',
        'subtotale',
        'service_type',
        'service_id',
    ];

    protected $casts = [
        'quantita' => 'decimal:2',
        'prezzo_unitario' => 'decimal:2',
        'sconto' => 'decimal:2',
        'iva_percentuale' => 'decimal:2',
        'subtotale' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            $item->calculateSubtotal();
        });
    }

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Helper methods
    public function calculateSubtotal()
    {
        $base = $this->quantita * $this->prezzo_unitario;
        $discount = $base * ($this->sconto / 100);
        $this->subtotale = $base - $discount;
    }

    public function getIvaAmountAttribute()
    {
        return $this->subtotale * ($this->iva_percentuale / 100);
    }

    public function getTotalWithIvaAttribute()
    {
        return $this->subtotale + $this->iva_amount;
    }
}
