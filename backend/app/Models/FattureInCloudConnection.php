<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FattureInCloudConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fic_company_id',
        'company_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'refresh_token_expires_at',
        'scopes',
        'is_active',
        'last_sync_at',
        'sync_stats',
        'pending_oauth_state',
        'oauth_state_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'oauth_state_expires_at' => 'datetime',
        'scopes' => 'array',
        'sync_stats' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a pending OAuth connection (not yet completed).
     */
    public function isPending(): bool
    {
        return !empty($this->pending_oauth_state) && !$this->is_active;
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        if ($this->isPending() || !$this->token_expires_at) {
            return true; // Pending connections have no valid token
        }
        return $this->token_expires_at->isPast();
    }

    /**
     * Check if the refresh token is expired.
     */
    public function isRefreshTokenExpired(): bool
    {
        if ($this->isPending() || !$this->refresh_token_expires_at) {
            return true; // Pending connections have no refresh token
        }
        return $this->refresh_token_expires_at->isPast();
    }

    /**
     * Check if the connection needs token refresh.
     */
    public function needsRefresh(): bool
    {
        if ($this->isPending() || !$this->token_expires_at) {
            return false; // Pending connections can't be refreshed
        }
        // Refresh if token expires in less than 1 hour
        return $this->token_expires_at->subHour()->isPast() && !$this->isRefreshTokenExpired();
    }

    /**
     * Update sync statistics.
     */
    public function updateSyncStats(array $stats): void
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_stats' => array_merge($this->sync_stats ?? [], $stats),
        ]);
    }
}
