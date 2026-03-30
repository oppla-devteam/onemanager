<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;
use App\Database\OpplaGuardedConnection;
use App\Database\OpplaGuardedConnector;
use App\Models\Client;
use App\Models\Delivery;
use App\Models\OnboardingSession;
use App\Observers\ClientObserver;
use App\Observers\DeliveryObserver;
use App\Observers\OnboardingSessionObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom Oppla guarded database connection
        $this->app['db']->extend('oppla_guarded', function ($config, $name) {
            $connector = new OpplaGuardedConnector();
            $connection = $connector->connect($config);

            return new OpplaGuardedConnection(
                $connection,
                $config['database'] ?? '',
                $config['prefix'] ?? '',
                $config
            );
        });

        // Registra observer per automatizzare fatturazione
        Delivery::observe(DeliveryObserver::class);

        // Registra observer per automazioni email onboarding
        OnboardingSession::observe(OnboardingSessionObserver::class);

        // Registra observer per generazione automatica contratti
        Client::observe(ClientObserver::class);
    }
}
