<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type', // 'entrata' o 'uscita'
        'parent_id',
        'color',
        'icon',
        'description',
        'keywords', // JSON array di parole chiave per auto-categorizzazione
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(AccountingCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AccountingCategory::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'category_id');
    }

    public function financialEntries(): HasMany
    {
        return $this->hasMany(FinancialEntry::class, 'category_id');
    }

    public function scopeEntrate($query)
    {
        return $query->where('type', 'entrata');
    }

    public function scopeUscite($query)
    {
        return $query->where('type', 'uscita');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Verifica se una descrizione corrisponde a questa categoria
     */
    public function matchesDescription(string $description): bool
    {
        if (empty($this->keywords)) {
            return false;
        }

        $description = strtolower($description);
        foreach ($this->keywords as $keyword) {
            if (str_contains($description, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
