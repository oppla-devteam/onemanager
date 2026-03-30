<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingSession extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'partner_id',
        'step_client_partner_completed',
        'step_stripe_confirmed',
        'step_restaurant_completed',
        'current_step',
        'temp_data',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'step_client_partner_completed' => 'boolean',
        'step_stripe_confirmed' => 'boolean',
        'step_restaurant_completed' => 'boolean',
        'temp_data' => 'array',
        'completed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function getProgressPercentage()
    {
        $completedSteps = collect([
            $this->step_client_partner_completed,
            $this->step_stripe_confirmed,
            $this->step_restaurant_completed,
        ])->filter()->count();

        return ($completedSteps / 3) * 100;
    }
}
