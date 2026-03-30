<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContractSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'signer_name',
        'signer_email',
        'signer_phone',
        'signer_role',
        'signing_order',
        'status',
        'signature_data',
        'signature_type',
        'ip_address',
        'user_agent',
        'device_info',
        'gps_location',
        'otp_code',
        'otp_sent_at',
        'otp_expires_at',
        'otp_attempts',
        'signature_token',
        'token_expires_at',
        'invited_at',
        'viewed_at',
        'signed_at',
        'declined_at',
        'decline_reason',
    ];

    protected $casts = [
        'otp_sent_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_attempts' => 'integer',
        'token_expires_at' => 'datetime',
        'invited_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'declined_at' => 'datetime',
        'signing_order' => 'integer',
    ];

    protected $hidden = [
        'otp_code',
        'signature_token',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($signature) {
            if (empty($signature->signature_token)) {
                $signature->signature_token = Str::random(64);
                $signature->token_expires_at = now()->addDays(30);
            }
        });
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Genera e invia OTP
     */
    public function generateOTP(): string
    {
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'otp_code' => bcrypt($otp),
            'otp_sent_at' => now(),
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 0,
        ]);
        
        return $otp; // Da inviare via email/SMS
    }

    /**
     * Verifica OTP
     */
    public function verifyOTP(string $otp): bool
    {
        // Verifica scadenza
        if (!$this->otp_expires_at || $this->otp_expires_at->isPast()) {
            return false;
        }
        
        // Verifica tentativi
        if ($this->otp_attempts >= 3) {
            return false;
        }
        
        // Incrementa tentativi
        $this->increment('otp_attempts');
        
        // Verifica OTP
        return password_verify($otp, $this->otp_code);
    }

    /**
     * Verifica validità token
     */
    public function isTokenValid(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isFuture();
    }

    /**
     * Rigenera token
     */
    public function regenerateToken(int $daysValid = 30): void
    {
        $this->update([
            'signature_token' => Str::random(64),
            'token_expires_at' => now()->addDays($daysValid),
        ]);
    }

    /**
     * Marca come firmato
     */
    public function markAsSigned(string $signatureData, string $signatureType): void
    {
        $this->update([
            'status' => 'signed',
            'signature_data' => $signatureData,
            'signature_type' => $signatureType,
            'signed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Marca come rifiutato
     */
    public function markAsDeclined(string $reason): void
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);
    }

    /**
     * Marca come visualizzato
     */
    public function markAsViewed(): void
    {
        if ($this->status === 'invited' && !$this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }
}
