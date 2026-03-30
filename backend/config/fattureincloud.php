<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fatture in Cloud API Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione per l'integrazione con Fatture in Cloud API v2
    | Recupera le chiavi API dal pannello Fatture in Cloud
    | https://secure.fattureincloud.it/api-keys
    |
    */

    'fattureincloud' => [
        'api_key' => env('FATTUREINCLOUD_API_KEY'),
        'api_uid' => env('FATTUREINCLOUD_API_UID'),
        'company_id' => env('FATTUREINCLOUD_COMPANY_ID'),
        'auto_send_sdi' => env('FATTUREINCLOUD_AUTO_SEND_SDI', true),
        'create_immediate_invoices' => env('FATTUREINCLOUD_CREATE_IMMEDIATE', true),
        'create_deferred_invoices' => env('FATTUREINCLOUD_CREATE_DEFERRED', true),
    ],
];
