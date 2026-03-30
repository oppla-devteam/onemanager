<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fatture in Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Fatture in Cloud API v2 integration with OAuth 2.0
    |
    */

    'base_url' => env('FIC_BASE_URL', 'https://api-v2.fattureincloud.it'),

    'oauth' => [
        'authorize_url' => 'https://api-v2.fattureincloud.it/oauth/authorize',
        'token_url' => 'https://api-v2.fattureincloud.it/oauth/token',
        'client_id' => env('FIC_CLIENT_ID'),
        'client_secret' => env('FIC_CLIENT_SECRET'),
        'redirect_uri' => env('FIC_REDIRECT_URI', env('APP_URL') . '/api/fatture-in-cloud/callback'),
    ],

    'scopes' => [
        // Settings scope
        'settings.all' => 'settings:a',
        
        // Entity scopes
        'entities.clients.read' => 'entity.clients:r',
        'entities.clients.write' => 'entity.clients:a',
        'entities.suppliers.read' => 'entity.suppliers:r',
        'entities.suppliers.write' => 'entity.suppliers:a',
        'entities.products.read' => 'entity.products:r',
        'entities.products.write' => 'entity.products:a',
        
        // Issued documents scopes (fatture attive)
        'issued_documents.invoices.read' => 'issued_documents.invoices:r',
        'issued_documents.invoices.write' => 'issued_documents.invoices:a',
        'issued_documents.quotes.read' => 'issued_documents.quotes:r',
        'issued_documents.quotes.write' => 'issued_documents.quotes:a',
        'issued_documents.receipts.read' => 'issued_documents.receipts:r',
        'issued_documents.receipts.write' => 'issued_documents.receipts:a',
        
        // Received documents scopes (fatture passive)
        'received_documents.read' => 'received_documents:r',
        'received_documents.write' => 'received_documents:a',
        
        // Taxes scopes
        'taxes.read' => 'taxes:r',
        'taxes.write' => 'taxes:a',
        
        // Archive scopes
        'archive.read' => 'archive:r',
        'archive.write' => 'archive:a',
        
        // Cashbook scopes
        'cashbook.read' => 'cashbook:r',
        'cashbook.write' => 'cashbook:a',
    ],

    // Default scopes to request (customize based on your needs)
    'default_scopes' => [
        'settings:a',
        'entity.clients:a',
        'entity.suppliers:a',
        'issued_documents.invoices:a',
        'received_documents:a',
        'cashbook:r',  // Movimenti di cassa per dashboard finanziaria
    ],

    // Token expiration times (in seconds)
    'token_expiration' => [
        'access_token' => 86400, // 24 hours
        'refresh_token' => 31536000, // 1 year
    ],

    // API request timeout (in seconds)
    'timeout' => env('FIC_TIMEOUT', 30),

    // Number of retry attempts for failed requests
    'retry_attempts' => env('FIC_RETRY_ATTEMPTS', 3),

    // Retry delay (in milliseconds)
    'retry_delay' => env('FIC_RETRY_DELAY', 1000),
];
