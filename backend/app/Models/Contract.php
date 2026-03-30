<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_number',
        'client_id',
        'client_name',
        'client_email',
        'client_phone',
        'client_vat_number',
        'client_fiscal_code',
        'subject',
        'title',
        'description',
        'contract_type',
        'contract_data',
        'terms',
        'partner_ragione_sociale',
        'partner_piva',
        'partner_sede_legale',
        'partner_email',
        'partner_legale_rappresentante',
        'partner_iban',
        'costo_attivazione',
        'periodo_mesi',
        'territorio',
        'miglior_prezzo_garantito',
        'notes',
        'note',
        'status',
        'start_date',
        'end_date',
        'duration_months',
        'auto_renew',
        'value',
        'currency',
        'billing_frequency',
        'file_path',
        'pdf_path',
        'signed_pdf_path',
        'sent_at',
        'signed_at',
        'signed_by',
        'activated_at',
        'expires_at',
        'signature_token',
        'signature_token_expires_at',
        'html_content',
        'version',
        'created_by',
        'assigned_to',
        'notify_expiration',
        'notify_days_before',
    ];

    protected $casts = [
        'contract_data' => 'array',
        'terms' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'signature_token_expires_at' => 'datetime',
        'version' => 'integer',
        'value' => 'decimal:2',
        'costo_attivazione' => 'decimal:2',
        'duration_months' => 'integer',
        'periodo_mesi' => 'integer',
        'auto_renew' => 'boolean',
        'miglior_prezzo_garantito' => 'boolean',
        'notify_expiration' => 'boolean',
        'notify_days_before' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = self::generateContractNumber();
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function signatures()
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function history()
    {
        return $this->hasMany(ContractHistory::class);
    }

    public function attachments()
    {
        return $this->hasMany(ContractAttachment::class);
    }

    /**
     * Genera numero contratto univoco
     */
    public static function generateContractNumber(): string
    {
        $year = date('Y');
        $lastContract = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();
        
        $number = $lastContract ? (int)substr($lastContract->contract_number, -4) + 1 : 1;
        
        return sprintf('CTR-%s-%04d', $year, $number);
    }

    /**
     * Verifica se tutte le firme sono completate
     */
    public function isFullySigned(): bool
    {
        $totalSignatures = $this->signatures()->count();
        $signedSignatures = $this->signatures()->where('status', 'signed')->count();
        
        return $totalSignatures > 0 && $totalSignatures === $signedSignatures;
    }

    /**
     * Verifica se il contratto è scaduto
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica se il contratto è attivo
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->start_date === null || $this->start_date->isPast()) &&
               !$this->isExpired();
    }

    /**
     * Ottieni prossimo firmatario in ordine
     */
    public function getNextSigner()
    {
        return $this->signatures()
            ->where('status', '!=', 'signed')
            ->orderBy('signing_order')
            ->first();
    }

    /**
     * Registra evento nello storico
     */
    public function logHistory(string $action, ?string $oldStatus = null, ?string $newStatus = null, ?array $changes = null, ?string $notes = null)
    {
        $this->history()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changes' => $changes,
            'notes' => $notes,
            'ip_address' => request()->ip(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now());
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('status', 'attivo')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }
}
