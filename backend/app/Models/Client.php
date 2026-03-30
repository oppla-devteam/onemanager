<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Client extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'guid',
        'fic_client_id',
        'type',
        'tipo_societa',
        'ragione_sociale',
        'piva',
        'codice_fiscale',
        'codice_fiscale_titolare',
        'email',
        'phone',
        'pec',
        'sdi_code',
        'indirizzo',
        'citta',
        'provincia',
        'cap',
        'nazione',
        'stripe_customer_id',
        'stripe_subscription_id',
        'has_domain',
        'has_pos',
        'has_delivery',
        'is_partner_logistico',
        'fee_mensile',
        'fee_ordine',
        'fee_consegna_base',
        'fee_consegna_km',
        'abbonamento_mensile',
        'status',
        'onboarding_date',
        'activation_date',
        'notes',
        'source', // Aggiunto per tracciare origine (manual, stripe_auto, etc)
        // Campi sincronizzazione OPPLA
        'oppla_external_id',
        'oppla_sync_at',
        'oppla_data',
        'oppla_restaurants',
        'oppla_restaurants_count',
    ];

    protected $casts = [
        'has_domain' => 'boolean',
        'has_pos' => 'boolean',
        'has_delivery' => 'boolean',
        'is_partner_logistico' => 'boolean',
        'fee_mensile' => 'decimal:2',
        'fee_ordine' => 'decimal:2',
        'fee_consegna_base' => 'decimal:2',
        'fee_consegna_km' => 'decimal:2',
        'abbonamento_mensile' => 'decimal:2',
        'onboarding_date' => 'date',
        'activation_date' => 'date',
        'oppla_sync_at' => 'datetime',
        'oppla_data' => 'array',
        'oppla_restaurants' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($client) {
            if (empty($client->guid)) {
                $client->guid = (string) Str::uuid();
            }
        });
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ragione_sociale', 'email', 'type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    public function clientServices()
    {
        return $this->hasMany(ClientService::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'client_services')
            ->withPivot(['start_date', 'end_date', 'price', 'is_active'])
            ->withTimestamps();
    }

    public function upsellings()
    {
        return $this->hasMany(UpsellingSale::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    // CRM Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function accountManager()
    {
        return $this->belongsTo(User::class, 'account_manager_id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'related');
    }

    public function communications()
    {
        return $this->morphMany(Communication::class, 'communicable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    // Onboarding relationships
    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    public function campaignMembers()
    {
        return $this->morphMany(CampaignMember::class, 'member');
    }

    // CRM Scopes
    public function scopeCustomers($query)
    {
        return $query->where('client_type', 'customer');
    }

    public function scopeProspects($query)
    {
        return $query->where('client_type', 'prospect');
    }

    public function scopeAtRisk($query)
    {
        return $query->where('health_score', 'at_risk');
    }

    public function scopeCritical($query)
    {
        return $query->where('health_score', 'critical');
    }

    public function scopeHighValue($query, float $threshold = 10000)
    {
        return $query->where('lifetime_value', '>=', $threshold);
    }

    // CRM Methods
    public function calculateLifetimeValue(): float
    {
        return $this->invoices()
            ->where('status', 'paid')
            ->sum('total_amount');
    }

    public function updateHealthScore(): void
    {
        $score = 'excellent';
        
        // Logica scoring
        $daysSinceLastOrder = $this->last_order_at ? now()->diffInDays($this->last_order_at) : 999;
        
        if ($daysSinceLastOrder > 60) {
            $score = 'critical';
        } elseif ($daysSinceLastOrder > 30) {
            $score = 'at_risk';
        } elseif ($this->satisfaction_score && $this->satisfaction_score < 7) {
            $score = 'at_risk';
        } elseif ($this->orders_count > 10 && $this->satisfaction_score >= 9) {
            $score = 'excellent';
        } else {
            $score = 'good';
        }
        
        $this->update(['health_score' => $score]);
    }

    public function addActivity(string $type, string $subject, array $data = []): Activity
    {
        return $this->activities()->create(array_merge([
            'type' => $type,
            'subject' => $subject,
            'created_by' => Auth::id(),
        ], $data));
    }

    public function addNote(string $content): Communication
    {
        return $this->communications()->create([
            'content' => $content,
            'type' => 'note',
            'user_id' => Auth::id(),
        ]);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePartnerOppla($query)
    {
        return $query->where('type', 'partner_oppla');
    }

    public function scopeClienteExtra($query)
    {
        return $query->where('type', 'cliente_extra');
    }

    public function scopeConsumatore($query)
    {
        return $query->where('type', 'consumatore');
    }

    // Accessors & Mutators
    public function getFullAddressAttribute()
    {
        return "{$this->indirizzo}, {$this->cap} {$this->citta} ({$this->provincia})";
    }

    // English accessors for compatibility with invoice services
    public function getBusinessNameAttribute()
    {
        return $this->ragione_sociale;
    }

    public function getVatNumberAttribute()
    {
        return $this->piva;
    }

    public function getTaxCodeAttribute()
    {
        return $this->codice_fiscale;
    }

    public function getAddressAttribute()
    {
        return $this->indirizzo;
    }

    public function getCityAttribute()
    {
        return $this->citta;
    }

    public function getProvinceAttribute()
    {
        return $this->provincia;
    }

    public function getZipCodeAttribute()
    {
        return $this->cap;
    }

    public function getPecEmailAttribute()
    {
        return $this->pec;
    }

    // Helper methods
    public function isPartnerOppla(): bool
    {
        return $this->type === 'partner_oppla';
    }

    public function isClienteExtra(): bool
    {
        return $this->type === 'cliente_extra';
    }

    public function hasActiveStripeSubscription(): bool
    {
        return !empty($this->stripe_subscription_id);
    }

    public function calculateMonthlyRevenue(): float
    {
        return $this->fee_mensile + $this->abbonamento_mensile;
    }
}
