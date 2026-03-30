<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Opplà Admin Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione per integrazione con OPPLA Delivery via Filament scraping.
    | Crea partner e ristoranti simulando submit dei form Filament.
    | Le email di benvenuto vengono inviate automaticamente da OPPLA.
    |
    */

    'base_url' => env('OPPLA_ADMIN_URL', 'https://api.oppla.delivery'),
    
    // GraphQL API Configuration (for read operations)
    'graphql_url' => env('OPPLA_GRAPHQL_URL', 'https://api.oppla.delivery/graphql'),
    'api_key' => env('OPPLA_API_KEY'),
    
    // Filament Admin Credentials (for scraping partner/restaurant creation)
    'credentials' => [
        'email' => env('OPPLA_ADMIN_EMAIL', 'lorenzo.moschella@oppla.delivery'),
        'password' => env('OPPLA_ADMIN_PASSWORD'),
    ],

    // Sync schedule: 3 volte al giorno
    'sync_times' => [
        '08:00',  // Mattina
        '14:00',  // Pomeriggio
        '20:00',  // Sera
    ],

    // Timeout per richieste HTTP (secondi)
    'timeout' => 30,

    // Retry attempts se fallisce
    'retry_attempts' => 3,

    // Cookie jar per mantenere sessione
    'cookie_file' => storage_path('app/oppla_admin_cookies.txt'),

    // Log file dedicato
    'log_file' => storage_path('logs/oppla_sync.log'),

    // Filament routes (deprecated - now using API)
    'routes' => [
        'login' => '/admin/login',
        'partners' => '/admin/partners',
        'restaurants' => '/admin/restaurants',
    ],
];
