<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'oppla_external_id',
        'client_id',
        'nome',
        'category',
        'description',
        'indirizzo',
        'citta',
        'provincia',
        'cap',
        'zone',
        'telefono',
        'email',
        'piva',
        'codice_fiscale',
        'logo_path',
        'foto_path',
        'cover_path',
        'cover_opacity',
        'delivery_management',
        'delivery_zones',
        'fee_class_id',
        'best_price',
        'is_active',
        'oppla_sync_at',
        // Partner Protection
        'incident_count_30d',
        'delay_count_30d',
        'bulky_unmarked_count_30d',
        'partner_status',
        'partner_suspended_at',
        'partner_suspension_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'best_price' => 'boolean',
        'cover_opacity' => 'integer',
        'delivery_zones' => 'array',
        'oppla_sync_at' => 'datetime',
        'incident_count_30d' => 'integer',
        'delay_count_30d' => 'integer',
        'bulky_unmarked_count_30d' => 'integer',
        'partner_suspended_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function partner()
    {
        return $this->hasOne(Partner::class);
    }

    public function feeClass()
    {
        return $this->belongsTo(FeeClass::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    // Partner Protection Relations
    public function partnerProtectionSettings()
    {
        return $this->hasOne(PartnerProtectionSettings::class);
    }

    public function incidents()
    {
        return $this->hasMany(PartnerIncident::class);
    }

    public function penalties()
    {
        return $this->hasMany(PartnerPenalty::class);
    }

    public function timeSlots()
    {
        return $this->hasMany(RestaurantTimeSlot::class);
    }

    public function allowedDeliveryZones()
    {
        return $this->hasMany(RestaurantDeliveryZone::class);
    }

    // Partner Protection Methods
    public function isPartnerActive(): bool
    {
        return $this->partner_status === 'active';
    }

    public function isPartnerSuspended(): bool
    {
        return $this->partner_status === 'suspended';
    }

    public function getEffectiveSettings(): array
    {
        return PartnerProtectionSettings::getEffectiveSettings($this->id);
    }
}
