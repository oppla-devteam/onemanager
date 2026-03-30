<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'client_id',
        'type',
        'invoice_type',
        'numero_fattura',
        'anno',
        'numero_progressivo',
        'data_emissione',
        'data_scadenza',
        'data_pagamento',
        'imponibile',
        'iva',
        'totale',
        'ritenuta_acconto',
        'totale_netto',
        'stripe_transaction_id',
        'fic_document_id',
        'fic_invoice_id',
        'fic_data',
        'sdi_status',
        'sdi_sent_at',
        'sdi_file_path',
        'pdf_file_path',
        'status',
        'payment_status',
        'payment_method',
        'note',
        'causale',
        // Intra-EU invoice fields
        'is_intra_eu',
        'is_reverse_charge',
        'vat_country',
        'client_vat_number',
    ];

    protected $casts = [
        'data_emissione' => 'date',
        'data_scadenza' => 'date',
        'data_pagamento' => 'date',
        'sdi_sent_at' => 'datetime',
        'imponibile' => 'decimal:2',
        'iva' => 'decimal:2',
        'totale' => 'decimal:2',
        'ritenuta_acconto' => 'decimal:2',
        'totale_netto' => 'decimal:2',
        'fic_data' => 'array',
        'is_intra_eu' => 'boolean',
        'is_reverse_charge' => 'boolean',
    ];

    protected $appends = [
        'importo_imponibile',
        'importo_iva',
        'importo_totale',
        'client_name',
        'numero_fattura_completo',
    ];

    // Accessors per compatibilità frontend
    public function getImportoImponibileAttribute()
    {
        return $this->imponibile;
    }

    public function getImportoIvaAttribute()
    {
        return $this->iva;
    }

    public function getImportoTotaleAttribute()
    {
        return $this->totale;
    }

    public function getClientNameAttribute()
    {
        return $this->client ? $this->client->ragione_sociale : '';
    }

    public function getNumeroFatturaCompletoAttribute()
    {
        // Concatena numero_fattura + anno per la visualizzazione
        // Esempio: "1/A" + 2026 = "1/A/2026"
        if ($this->numero_fattura && $this->anno) {
            return $this->numero_fattura . '/' . $this->anno;
        }
        return $this->numero_fattura;
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            // REMOVED: Automatic invoice number generation in creating event
            // to prevent race conditions. Number should be generated explicitly
            // WITHIN database transactions using lockForUpdate().
            // Only generate if both numero_fattura AND numero_progressivo are empty
            if (empty($invoice->numero_fattura) && empty($invoice->numero_progressivo)) {
                $invoice->generateInvoiceNumber();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['numero_fattura', 'totale', 'status', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    // Scopes
    public function scopeAttiva($query)
    {
        return $query->where('type', 'attiva');
    }

    public function scopePassiva($query)
    {
        return $query->where('type', 'passiva');
    }

    public function scopeEmessa($query)
    {
        return $query->where('status', 'emessa');
    }

    public function scopePagata($query)
    {
        return $query->where('payment_status', 'pagata');
    }

    public function scopeScadute($query)
    {
        return $query->where('data_scadenza', '<', now())
            ->where('payment_status', '!=', 'pagata');
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('anno', $year);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('data_emissione', $year)
            ->whereMonth('data_emissione', $month);
    }

    // Helper methods
    public function generateInvoiceNumber()
    {
        // Anno corrente della fattura (es. 2026 quando fattura generata a gennaio 2026)
        $annoFattura = date('Y');
        
        // Sincronizza con Fatture in Cloud per ottenere l'ultimo progressivo
        $ficService = app(\App\Services\FattureInCloudService::class);
        $ficLastNumber = $ficService->getLastInvoiceNumber($annoFattura);
        
        // Lock FOR UPDATE per evitare race condition
        // IMPORTANTE: withoutTrashed() per escludere fatture soft-deleted
        // Per fatture attive (ordinarie e differite), condividono lo stesso progressivo
        if ($this->type === 'attiva') {
            $lastInvoice = self::withoutTrashed()
                ->where('anno', $annoFattura)
                ->where('type', 'attiva')
                ->whereIn('invoice_type', ['ordinaria', 'differita'])
                ->lockForUpdate()
                ->orderBy('numero_progressivo', 'desc')
                ->first();
        } else {
            $lastInvoice = self::withoutTrashed()
                ->where('anno', $annoFattura)
                ->where('type', $this->type)
                ->where('invoice_type', $this->invoice_type)
                ->lockForUpdate()
                ->orderBy('numero_progressivo', 'desc')
                ->first();
        }

        $localLastNumber = $lastInvoice ? $lastInvoice->numero_progressivo : 0;

        // Usa il maggiore tra FIC e locale come massimo progressivo
        // Minimo 57: la numerazione 2026 riparte da 58 (riallineamento)
        $maxProgressivo = max($ficLastNumber, $localLastNumber, 57);

        // Per fatture Attiva (sia ordinarie che differite): usa suffisso /A
        if ($this->type === 'attiva') {
            $suffisso = 'A'; // Sia ordinarie che differite usano /A
        } else {
            // Fallback per altri tipi
            $tipoSigla = strtoupper(substr($this->type, 0, 1));
            $suffisso = $this->invoice_type === 'differita' ? $tipoSigla . 'D' : $tipoSigla . 'O';
        }

        // PRIMA: Cerca eventuali "buchi" nella numerazione (numeri saltati)
        // Recupera tutti i numeri progressivi esistenti per questo anno/tipo
        if ($this->type === 'attiva') {
            $existingNumbers = self::withoutTrashed()
                ->where('anno', $annoFattura)
                ->where('type', 'attiva')
                ->whereIn('invoice_type', ['ordinaria', 'differita'])
                ->orderBy('numero_progressivo', 'asc')
                ->pluck('numero_progressivo')
                ->toArray();
        } else {
            $existingNumbers = self::withoutTrashed()
                ->where('anno', $annoFattura)
                ->where('type', $this->type)
                ->where('invoice_type', $this->invoice_type)
                ->orderBy('numero_progressivo', 'asc')
                ->pluck('numero_progressivo')
                ->toArray();
        }
        
        $progressivo = null;
        
        // Cerca il primo "buco" nella sequenza (numero mancante)
        // Parti dall'ultimo numero locale+1 per cercare buchi solo nella parte alta della sequenza
        // Non riempire buchi sotto il minimo forzato (57)
        $minFloor = 57;
        if (!empty($existingNumbers)) {
            $startFrom = max(min($existingNumbers), $minFloor + 1);
            for ($i = $startFrom; $i <= $maxProgressivo; $i++) {
                if (!in_array($i, $existingNumbers)) {
                    // Trovato un buco! Usa questo numero
                    $progressivo = $i;
                    Log::info('Trovato buco nella numerazione fatture', [
                        'anno' => $annoFattura,
                        'progressivo_mancante' => $progressivo,
                        'max_progressivo' => $maxProgressivo
                    ]);
                    break;
                }
            }
        }
        
        // Se non ci sono buchi, usa il prossimo numero dopo il massimo
        if ($progressivo === null) {
            $progressivo = $maxProgressivo + 1;
            Log::info('Nessun buco trovato, uso progressivo successivo', [
                'anno' => $annoFattura,
                'fic_last' => $ficLastNumber,
                'local_last' => $localLastNumber,
                'new_progressivo' => $progressivo
            ]);
        }
        
        // Verifica finale che il numero sia effettivamente libero
        $numeroFattura = sprintf('%d/%s', $progressivo, $suffisso);

        if ($this->type === 'attiva') {
            $exists = self::withoutTrashed()
                ->where('numero_fattura', $numeroFattura)
                ->where('type', 'attiva')
                ->whereIn('invoice_type', ['ordinaria', 'differita'])
                ->where('anno', $annoFattura)
                ->where('id', '!=', $this->id ?? 0)
                ->lockForUpdate()
                ->exists();
        } else {
            $exists = self::withoutTrashed()
                ->where('numero_fattura', $numeroFattura)
                ->where('type', $this->type)
                ->where('invoice_type', $this->invoice_type)
                ->where('anno', $annoFattura)
                ->where('id', '!=', $this->id ?? 0)
                ->lockForUpdate()
                ->exists();
        }
        
        if (!$exists) {
            // Numero libero, assegna
            $this->numero_progressivo = $progressivo;
            $this->anno = $annoFattura;
            $this->numero_fattura = $numeroFattura;
            return;
        }
        
        // Situazione anomala: il numero calcolato esiste già
        // Fallback: cerca il primo numero libero incrementando
        Log::warning('Numero fattura calcolato già esistente, uso fallback', [
            'numero_calcolato' => $numeroFattura,
            'anno' => $annoFattura
        ]);
        
        $maxAttempts = 100;
        $attempts = 0;
        $progressivo = $maxProgressivo + 1;
        
        while ($attempts < $maxAttempts) {
            $numeroFattura = sprintf('%d/%s', $progressivo, $suffisso);

            if ($this->type === 'attiva') {
                $exists = self::withoutTrashed()
                    ->where('numero_fattura', $numeroFattura)
                    ->where('type', 'attiva')
                    ->whereIn('invoice_type', ['ordinaria', 'differita'])
                    ->where('anno', $annoFattura)
                    ->where('id', '!=', $this->id ?? 0)
                    ->lockForUpdate()
                    ->exists();
            } else {
                $exists = self::withoutTrashed()
                    ->where('numero_fattura', $numeroFattura)
                    ->where('type', $this->type)
                    ->where('invoice_type', $this->invoice_type)
                    ->where('anno', $annoFattura)
                    ->where('id', '!=', $this->id ?? 0)
                    ->lockForUpdate()
                    ->exists();
            }

            if (!$exists) {
                $this->numero_progressivo = $progressivo;
                $this->anno = $annoFattura;
                $this->numero_fattura = $numeroFattura;
                return;
            }

            $progressivo++;
            $attempts++;
        }
        
        throw new \RuntimeException("Impossibile generare numero fattura univoco dopo {$maxAttempts} tentativi");
    }

    public function addItem(array $data)
    {
        return $this->items()->create($data);
    }

    public function calculateTotals()
    {
        $this->imponibile = $this->items->sum('subtotale');
        $this->iva = $this->items->sum(function ($item) {
            return $item->subtotale * ($item->iva_percentuale / 100);
        });
        $this->totale = $this->imponibile + $this->iva;
        $this->totale_netto = $this->totale - $this->ritenuta_acconto;
        $this->save();
    }

    public function markAsPaid($paymentDate = null, $paymentMethod = null)
    {
        $this->payment_status = 'pagata';
        $this->data_pagamento = $paymentDate ?? now();
        $this->payment_method = $paymentMethod;
        $this->save();
    }

    public function isScaduta(): bool
    {
        return $this->data_scadenza < now() && $this->payment_status !== 'pagata';
    }

    /**
     * Recalculate invoice totals based on remaining items
     */
    public function recalculateTotals()
    {
        $items = $this->items;
        
        if ($items->isEmpty()) {
            // Se non ci sono più items, cancella la fattura
            $this->delete();
            return;
        }
        
        $imponibile = $items->sum(function($item) {
            return $item->quantita * $item->prezzo_unitario;
        });
        
        $iva = $imponibile * 0.22; // IVA 22%
        $totale = $imponibile + $iva;
        
        $this->update([
            'imponibile' => $imponibile,
            'iva' => $iva,
            'totale' => $totale,
            'totale_netto' => $totale - ($this->ritenuta_acconto ?? 0)
        ]);
        
        \Log::info('Invoice totals recalculated', [
            'invoice_id' => $this->id,
            'items_count' => $items->count(),
            'new_total' => $totale
        ]);
    }
}
