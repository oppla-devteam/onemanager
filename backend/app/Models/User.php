<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    /**
     * Check if user has permission to access a specific route/section
     */
    public function hasPermission(string $permission): bool
    {
        // Check Spatie roles first
        try {
            if ($this->hasRole('super-admin') || $this->hasRole('admin')) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore if Spatie roles not set up
        }

        // Check Spatie permissions
        try {
            if ($this->can($permission)) {
                return true;
            }
        } catch (\Exception $e) {
            // Try to check if permission exists in permissions array
            $permissions = $this->getPermissions();
            if (in_array($permission, $permissions)) {
                return true;
            }
        }

        // Fallback to old permission system
        if ($this->role === 'admin') {
            return true;
        }

        if (!$this->permissions) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Get user permissions array
     */
    public function getPermissions(): array
    {
        // Check Spatie roles
        try {
            if ($this->hasRole('super-admin') || $this->hasRole('admin')) {
                return ['dashboard', 'clients', 'contracts', 'tasks', 'invoices', 'deliveries', 'accounting', 'crm', 'orders', 'menu', 'riders'];
            }
        } catch (\Exception $e) {
            // Ignore if Spatie roles not set up
        }

        // Get Spatie permissions - use direct relation instead of getAllPermissions()
        try {
            // Load relations if needed
            $this->loadMissing(['permissions', 'roles.permissions']);
            
            // Get direct permissions
            $directPermissions = $this->permissions ? $this->permissions->pluck('name')->toArray() : [];
            
            // Get permissions from roles
            $rolePermissions = [];
            if ($this->roles) {
                foreach ($this->roles as $role) {
                    if ($role->permissions) {
                        $rolePermissions = array_merge($rolePermissions, $role->permissions->pluck('name')->toArray());
                    }
                }
            }
            
            $spatiePermissions = array_unique(array_merge($directPermissions, $rolePermissions));
            
            if (!empty($spatiePermissions)) {
                return $spatiePermissions;
            }
        } catch (\Exception $e) {
            // Fallback if Spatie permissions fail
        }

        // Fallback to old permission system
        if ($this->role === 'admin') {
            return ['dashboard', 'clients', 'contracts', 'tasks', 'invoices', 'deliveries', 'accounting', 'crm', 'orders', 'menu', 'riders'];
        }

        return $this->permissions ?? [];
    }

    /**
     * Get push subscriptions for this user
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Task boards assegnate a questo utente
     */
    public function taskBoards()
    {
        return $this->belongsToMany(TaskBoard::class, 'task_board_user')
            ->withTimestamps();
    }
}
