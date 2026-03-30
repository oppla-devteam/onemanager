<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'entry_type',
        'description',
        'amount',
        'paid_amount',
        'date',
        'due_date',
        'is_recurring',
        'recurring_interval',
        'next_renewal_date',
        'vendor_name',
        'notes',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
        'next_renewal_date' => 'date',
        'is_recurring' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['remaining_amount', 'is_overdue', 'entry_type_label'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountingCategory::class, 'category_id');
    }

    // Scopes

    public function scopeCostiFissi($query)
    {
        return $query->where('entry_type', 'costo_fisso');
    }

    public function scopeCostiVariabili($query)
    {
        return $query->where('entry_type', 'costo_variabile');
    }

    public function scopeEntrateFisse($query)
    {
        return $query->where('entry_type', 'entrata_fissa');
    }

    public function scopeEntrateVariabili($query)
    {
        return $query->where('entry_type', 'entrata_variabile');
    }

    public function scopeDebiti($query)
    {
        return $query->where('entry_type', 'debito');
    }

    public function scopeCrediti($query)
    {
        return $query->where('entry_type', 'credito');
    }

    public function scopeCosts($query)
    {
        return $query->whereIn('entry_type', ['costo_fisso', 'costo_variabile']);
    }

    public function scopeIncomes($query)
    {
        return $query->whereIn('entry_type', ['entrata_fissa', 'entrata_variabile']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeRenewingSoon($query, int $days = 30)
    {
        return $query->where('is_recurring', true)
            ->whereNotNull('next_renewal_date')
            ->where('next_renewal_date', '<=', now()->addDays($days))
            ->where('next_renewal_date', '>=', now())
            ->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Computed Attributes

    public function getRemainingAmountAttribute(): float
    {
        return round($this->amount - $this->paid_amount, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date < now() && $this->status === 'active';
    }

    public function getEntryTypeLabelAttribute(): string
    {
        return match ($this->entry_type) {
            'costo_fisso' => 'Costo Fisso',
            'costo_variabile' => 'Costo Variabile',
            'entrata_fissa' => 'Entrata Fissa',
            'entrata_variabile' => 'Entrata Variabile',
            'debito' => 'Debito',
            'credito' => 'Credito',
            default => $this->entry_type,
        };
    }
}
