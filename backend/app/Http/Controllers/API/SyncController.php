<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use App\Services\FattureInCloudService;
use App\Models\FattureInCloudConnection;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Sincronizza tutto: Fatture da Fatture in Cloud + Pagamenti da Stripe
     */
    public function syncAll(Request $request)
    {
        // Aumenta il timeout per la sincronizzazione
        set_time_limit(300); // 5 minuti
        
        // Log iniziale per debugging
        Log::info('[SyncAll] START', [
            'timestamp' => now(),
            'user' => $request->user()->email ?? 'unknown',
            'env' => app()->environment(),
        ]);
        
        try {
            $results = [
                'invoices_synced' => 0,
                'clients_synced' => 0,
                'payments_imported' => 0,
                'oppla_partners' => 0,
                'oppla_restaurants' => 0,
                'oppla_delivery_zones' => 0,
                'oppla_orders' => 0,
                'oppla_managed_deliveries' => 0,
                'errors' => []
            ];

            // 1. Sincronizza fatture con Fatture in Cloud
            try {
                Log::info('[SyncAll] Inizio sincronizzazione Fatture in Cloud');
                
                $connection = FattureInCloudConnection::where('is_active', true)
                    ->first();

                if ($connection) {
                    $ficService = app(FattureInCloudService::class);
                    
                    // Sync invoices from Fatture in Cloud to local DB
                    $invoices = $ficService->getIssuedDocuments($connection);
                    
                    if ($invoices && isset($invoices['data'])) {
                        $syncCount = 0;
                        $clientCount = 0;
                        foreach ($invoices['data'] as $ficInvoice) {
                            try {
                                // Cerca o crea il cliente
                                $client = null;
                                if (isset($ficInvoice['entity']['vat_number'])) {
                                    $client = Client::where('piva', $ficInvoice['entity']['vat_number'])->first();
                                } elseif (isset($ficInvoice['entity']['tax_code'])) {
                                    $client = Client::where('codice_fiscale', $ficInvoice['entity']['tax_code'])->first();
                                }
                                
                                // Se il cliente non esiste, creane uno nuovo
                                if (!$client && isset($ficInvoice['entity']['name'])) {
                                    $client = Client::create([
                                        'guid' => (string) \Illuminate\Support\Str::uuid(),
                                        'type' => 'company',
                                        'ragione_sociale' => $ficInvoice['entity']['name'],
                                        'piva' => $ficInvoice['entity']['vat_number'] ?? null,
                                        'codice_fiscale' => $ficInvoice['entity']['tax_code'] ?? null,
                                        'email' => $ficInvoice['entity']['email'] ?? null,
                                        'phone' => $ficInvoice['entity']['phone'] ?? null,
                                        'pec' => $ficInvoice['entity']['certified_email'] ?? null,
                                        'sdi_code' => $ficInvoice['entity']['ei_code'] ?? null,
                                        'indirizzo' => $ficInvoice['entity']['address_street'] ?? null,
                                        'citta' => $ficInvoice['entity']['address_city'] ?? null,
                                        'provincia' => $ficInvoice['entity']['address_province'] ?? null,
                                        'cap' => $ficInvoice['entity']['address_zip'] ?? null,
                                        'nazione' => $ficInvoice['entity']['country'] ?? 'IT',
                                        'status' => 'active',
                                    ]);
                                    $clientCount++; // Incrementa il contatore dei nuovi clienti
                                }
                                
                                if ($client) {
                                    // Salva o aggiorna la fattura
                                    Invoice::updateOrCreate(
                                        ['fic_invoice_id' => $ficInvoice['id']],
                                        [
                                            'client_id' => $client->id,
                                            'numero_fattura' => $ficInvoice['number'] ?? 'N/A',
                                            'anno' => isset($ficInvoice['date']) ? date('Y', strtotime($ficInvoice['date'])) : now()->year,
                                            'numero_progressivo' => $ficInvoice['numeration'] ?? 0,
                                            'data_emissione' => $ficInvoice['date'] ?? now(),
                                            'imponibile' => $ficInvoice['amount_net'] ?? 0,
                                            'iva' => $ficInvoice['amount_vat'] ?? 0,
                                            'totale' => $ficInvoice['amount_gross'] ?? 0,
                                            'status' => $this->mapFicStatus($ficInvoice['status'] ?? null),
                                            'payment_status' => $this->mapFicPaymentStatus($ficInvoice['status'] ?? null),
                                            'fic_document_id' => $ficInvoice['id'],
                                            'fic_data' => $ficInvoice,
                                            'type' => 'attiva',
                                            'invoice_type' => 'ordinaria',
                                        ]
                                    );
                                    $syncCount++;
                                }
                            } catch (\Exception $e) {
                                Log::warning('[SyncAll] Errore sync fattura FIC', [
                                    'fic_id' => $ficInvoice['id'] ?? 'unknown',
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        $results['invoices_synced'] = $syncCount;
                        $results['clients_synced'] = $clientCount;
                        Log::info('[SyncAll] Fatture in Cloud completato', ['synced' => $syncCount, 'clients' => $clientCount]);
                    }
                } else {
                    Log::info('[SyncAll] Nessuna connessione FIC attiva');
                }
            } catch (\Exception $e) {
                Log::error('[SyncAll] Errore Fatture in Cloud: ' . $e->getMessage());
                $results['errors'][] = 'Fatture in Cloud: ' . $e->getMessage();
            }

            // 2. Importa pagamenti da Stripe
            try {
                Log::info('[SyncAll] Inizio importazione pagamenti Stripe');
                $stripeService = new StripeService();
                $startDate = now()->subDays(30);
                $endDate = now();
                
                $stripeResult = $stripeService->importTransactions($startDate, $endDate);
                $results['payments_imported'] = $stripeResult['imported'] ?? 0;
                
                Log::info('[SyncAll] Stripe completato', ['imported' => $results['payments_imported']]);
            } catch (\Exception $e) {
                Log::error('[SyncAll] Errore Stripe: ' . $e->getMessage());
                $results['errors'][] = 'Stripe: ' . $e->getMessage();
            }

            // 3. Sincronizza partners OPPLA dal database PostgreSQL (READ-ONLY)
            try {
                Log::info('[SyncAll] Inizio sincronizzazione OPPLA da PostgreSQL');
                
                // Verifica che la connessione PostgreSQL sia configurata
                if (!config('database.connections.oppla_readonly')) {
                    Log::warning('[SyncAll] Connessione oppla_readonly non configurata');
                    throw new \Exception('Connessione database OPPLA non configurata');
                }
                
                // Test connessione con timeout
                try {
                    \DB::connection('oppla_readonly')->getPdo();
                } catch (\Exception $e) {
                    Log::error('[SyncAll] Impossibile connettersi al database OPPLA: ' . $e->getMessage());
                    throw new \Exception('Database OPPLA non disponibile: ' . $e->getMessage());
                }
                
                // Connessione READ-ONLY al database PostgreSQL di Oppla - tabella users
                $opplaUsers = \DB::connection('oppla_readonly')
                    ->table('users')
                    ->where('type', 'partner')
                    ->whereNull('deleted_at')
                    ->get();
                
                $syncedCount = 0;
                foreach ($opplaUsers as $user) {
                    try {
                        // Crea o aggiorna il cliente nel DB locale SQLite
                        Client::updateOrCreate(
                            [
                                'oppla_external_id' => $user->id, // ID univoco da Oppla
                            ],
                            [
                                'guid' => (string) \Illuminate\Support\Str::uuid(),
                                'type' => 'partner_oppla',
                                'ragione_sociale' => $user->name ?? ($user->first_name && $user->last_name ? $user->first_name . ' ' . $user->last_name : 'Partner Oppla'),
                                'email' => $user->email,
                                'phone' => $user->phone ?? null,
                                'stripe_customer_id' => $user->stripe_id ?? null,
                                'status' => 'active',
                                'oppla_sync_at' => now(),
                            ]
                        );
                        $syncedCount++;
                    } catch (\Exception $e) {
                        Log::warning('[SyncAll] Errore sync partner OPPLA', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $results['oppla_partners'] = $syncedCount;
                Log::info('[SyncAll] OPPLA users completato', ['synced' => $syncedCount]);
                
            } catch (\Exception $e) {
                Log::error('[SyncAll] Errore OPPLA users: ' . $e->getMessage());
                $results['errors'][] = 'OPPLA users: ' . $e->getMessage();
            }

            // 3b. Sincronizza ristoranti OPPLA
            try {
                Log::info('[SyncAll] Inizio sincronizzazione ristoranti OPPLA');
                
                $opplaRestaurants = \DB::connection('oppla_readonly')
                    ->table('restaurants')
                    ->get();
                
                $restaurantsSyncedCount = 0;
                foreach ($opplaRestaurants as $restaurant) {
                    try {
                        // Trova il partner/user associato tramite owner_id
                        $client = Client::where('oppla_external_id', $restaurant->owner_id ?? null)->first();
                        
                        if ($client) {
                            // Aggiorna info ristorante nel client
                            $client->update([
                                'has_delivery' => $restaurant->accepts_deliveries ?? false,
                                'oppla_data' => array_merge($client->oppla_data ?? [], [
                                    'restaurant' => [
                                        'id' => $restaurant->id,
                                        'name' => $restaurant->name ?? null,
                                        'slug' => $restaurant->slug ?? null,
                                        'address' => $restaurant->address ?? null,
                                        'phone' => $restaurant->phone ?? null,
                                        'description' => $restaurant->description ?? null,
                                        'location' => $restaurant->location ?? null,
                                        'delivery_area' => $restaurant->delivery_area ?? null,
                                        'average_rating' => $restaurant->average_rating ?? null,
                                        'preparation_time_minutes' => $restaurant->preparation_time_minutes ?? null,
                                        'accepts_pickups' => $restaurant->accepts_pickups ?? false,
                                        'accepts_deliveries' => $restaurant->accepts_deliveries ?? false,
                                        'accepts_cash' => $restaurant->accepts_cash ?? false,
                                        'platform_fee' => $restaurant->platform_fee ?? 0,
                                        'order_price' => $restaurant->order_price ?? 0,
                                    ]
                                ])
                            ]);
                            $restaurantsSyncedCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning('[SyncAll] Errore sync ristorante OPPLA', [
                            'restaurant_id' => $restaurant->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $results['oppla_restaurants'] = $restaurantsSyncedCount;
                Log::info('[SyncAll] OPPLA ristoranti completato', ['synced' => $restaurantsSyncedCount]);
                
            } catch (\Exception $e) {
                Log::error('[SyncAll] Errore OPPLA ristoranti: ' . $e->getMessage());
                $results['errors'][] = 'OPPLA ristoranti: ' . $e->getMessage();
            }

            // 3c. Sincronizza zone di consegna OPPLA - DISABILITATO (esegui solo da comando artisan alle 3 di notte)
            // Le zone di consegna richiedono ~40 secondi e vengono sincronizzate solo tramite cron job
            $results['oppla_delivery_zones'] = 0;
            Log::info('[SyncAll] OPPLA zone consegna saltato (usa comando artisan oppla:sync-zones)');

            // 4. Sincronizza ordini OPPLA dal database PostgreSQL
            try {
                Log::info('[SyncAll] Inizio sincronizzazione ordini OPPLA');
                
                // Connessione READ-ONLY al database PostgreSQL di Oppla
                $opplaOrders = \DB::connection('oppla_readonly')
                    ->table('orders')
                    ->where('created_at', '>=', now()->subMonths(12)) // Ultimi 12 mesi
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $ordersSyncedCount = 0;
                foreach ($opplaOrders as $opplaOrder) {
                    try {
                        // Trova il ristorante
                        $restaurant = \DB::connection('oppla_readonly')
                            ->table('restaurants')
                            ->where('id', $opplaOrder->restaurant_id)
                            ->first();
                        
                        if ($restaurant) {
                            // Trova il cliente tramite owner_id del ristorante
                            $client = Client::where('oppla_external_id', $restaurant->owner_id)->first();
                            
                            if ($client) {
                                // Crea o aggiorna l'ordine
                                Order::updateOrCreate(
                                    [
                                        'oppla_order_id' => $opplaOrder->id,
                                    ],
                                    [
                                        'client_id' => $client->id,
                                        'order_number' => $opplaOrder->number ?? "ORD-{$opplaOrder->id}",
                                        'order_date' => $opplaOrder->date ?? $opplaOrder->created_at,
                                        'total_amount' => $opplaOrder->total ?? 0,
                                        'currency' => 'EUR',
                                        'status' => $this->mapOpplaOrderStatus($opplaOrder->status ?? 'pending'),
                                        'items' => null, // Il carrello è in una tabella separata
                                        'items_count' => 0,
                                        'shipping_address' => $opplaOrder->delivery_address ?? null,
                                        'shipping_city' => null,
                                        'shipping_province' => null,
                                        'shipping_cap' => null,
                                        'shipping_country' => 'IT',
                                        'tracking_number' => null,
                                        'carrier' => null,
                                        'oppla_sync_at' => now(),
                                        'oppla_data' => [
                                            'restaurant_id' => $opplaOrder->restaurant_id ?? null,
                                            'customer_name' => $opplaOrder->customer_name ?? null,
                                            'customer_phone' => $opplaOrder->customer_phone ?? null,
                                            'delivery_type' => $opplaOrder->delivery_type ?? 'delivery',
                                            'subtotal' => $opplaOrder->subtotal ?? 0,
                                            'delivery_fee' => $opplaOrder->delivery_fee ?? 0,
                                            'discount' => $opplaOrder->discount ?? 0,
                                            'platform_fee' => $opplaOrder->platform_fee ?? 0,
                                            'application_fee' => $opplaOrder->application_fee ?? 0,
                                            'payment_method' => $opplaOrder->payment_method ?? null,
                                            'notes' => $opplaOrder->notes ?? null,
                                            'delivery_notes' => $opplaOrder->delivery_notes ?? null,
                                            'stripe_payment_intent_id' => $opplaOrder->stripe_payment_intent_id ?? null,
                                            'accepted_at' => $opplaOrder->accepted_at ?? null,
                                            'delivered_at' => $opplaOrder->delivered_at ?? null,
                                            'paid_at' => $opplaOrder->paid_at ?? null,
                                        ],
                                    ]
                                );
                                $ordersSyncedCount++;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('[SyncAll] Errore sync ordine OPPLA', [
                            'order_id' => $opplaOrder->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $results['oppla_orders'] = $ordersSyncedCount;
                Log::info('[SyncAll] OPPLA ordini completato', ['synced' => $ordersSyncedCount]);
                
            } catch (\Exception $e) {
                Log::error('[SyncAll] Errore OPPLA ordini: ' . $e->getMessage());
                $results['errors'][] = 'OPPLA ordini: ' . $e->getMessage();
            }

            // 5. Sincronizza consegne gestite (managed_deliveries) da OPPLA
            try {
                Log::info('[SyncAll] Inizio sincronizzazione managed_deliveries OPPLA');
                
                $opplaDeliveries = \DB::connection('oppla_readonly')
                    ->table('managed_deliveries')
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                $deliveriesSyncedCount = 0;
                foreach ($opplaDeliveries as $delivery) {
                    try {
                        // Trova l'ordine associato
                        $order = Order::where('oppla_order_id', $delivery->order_id)->first();
                        
                        if ($order) {
                            // Aggiorna i dati di consegna nell'ordine
                            $order->update([
                                'delivery_status' => $delivery->status ?? null,
                                'tracking_number' => $delivery->tracking_number ?? null,
                                'carrier' => $delivery->carrier ?? null,
                                'delivered_at' => $delivery->delivered_at ?? null,
                                'oppla_data' => array_merge($order->oppla_data ?? [], [
                                    'managed_delivery' => [
                                        'id' => $delivery->id,
                                        'driver_name' => $delivery->driver_name ?? null,
                                        'driver_phone' => $delivery->driver_phone ?? null,
                                        'pickup_time' => $delivery->pickup_time ?? null,
                                        'delivery_time' => $delivery->delivery_time ?? null,
                                        'distance_km' => $delivery->distance_km ?? null,
                                        'delivery_cost' => $delivery->delivery_cost ?? null,
                                        'status' => $delivery->status ?? null,
                                        'notes' => $delivery->notes ?? null,
                                    ]
                                ])
                            ]);
                            $deliveriesSyncedCount++;
                        }
                    } catch (\Exception $e) {
                        Log::warning('[SyncAll] Errore sync managed_delivery', [
                            'delivery_id' => $delivery->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                $results['oppla_managed_deliveries'] = $deliveriesSyncedCount;
                Log::info('[SyncAll] OPPLA managed_deliveries completato', ['synced' => $deliveriesSyncedCount]);
                
            } catch (\Exception $e) {
                Log::error('[SyncAll] Errore OPPLA managed_deliveries: ' . $e->getMessage());
                $results['errors'][] = 'OPPLA managed_deliveries: ' . $e->getMessage();
            }

            $success = empty($results['errors']) || 
                       ($results['payments_imported'] > 0 || $results['invoices_synced'] > 0 || 
                        $results['oppla_partners'] > 0 || $results['oppla_orders'] > 0);

            $statusCode = $success ? 200 : 207; // 207 = Multi-Status (successo parziale)

            return response()->json([
                'success' => $success,
                'message' => $this->buildMessage($results),
                'data' => $results,
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('[SyncAll] Errore generale: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
            ]);
            
            // Restituisci comunque i risultati parziali invece di un errore 500
            return response()->json([
                'success' => false,
                'message' => 'Sincronizzazione completata con errori',
                'error' => $e->getMessage(),
                'error_details' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'type' => get_class($e),
                ],
                'data' => [
                    'invoices_synced' => 0,
                    'clients_synced' => 0,
                    'payments_imported' => 0,
                    'oppla_partners' => 0,
                    'oppla_restaurants' => 0,
                    'oppla_delivery_zones' => 0,
                    'oppla_orders' => 0,
                    'oppla_managed_deliveries' => 0,
                    'errors' => [$e->getMessage()]
                ]
            ], 200); // 200 invece di 207 per evitare problemi con interceptor
        }
    }

    private function mapFicStatus($status)
    {
        $mapping = [
            'paid' => 'pagata',
            'not_paid' => 'emessa',
            'reversed' => 'stornata',
            'partially_paid' => 'emessa',
        ];

        return $mapping[$status] ?? 'emessa';
    }

    private function mapFicPaymentStatus($status)
    {
        $mapping = [
            'paid' => 'pagata',
            'not_paid' => 'non_pagata',
            'reversed' => 'non_pagata',
            'partially_paid' => 'parzialmente_pagata',
        ];

        return $mapping[$status] ?? 'non_pagata';
    }

    private function buildMessage($results)
    {
        $parts = [];
        
        if ($results['invoices_synced'] > 0) {
            $parts[] = "{$results['invoices_synced']} fatture";
        }
        
        if ($results['clients_synced'] > 0) {
            $parts[] = "{$results['clients_synced']} clienti";
        }
        
        if ($results['payments_imported'] > 0) {
            $parts[] = "{$results['payments_imported']} pagamenti Stripe";
        }
        
        if ($results['oppla_partners'] > 0) {
            $parts[] = "{$results['oppla_partners']} partners OPPLA";
        }

        if ($results['oppla_orders'] > 0) {
            $parts[] = "{$results['oppla_orders']} ordini OPPLA";
        }

        if ($results['oppla_restaurants'] > 0) {
            $parts[] = "{$results['oppla_restaurants']} ristoranti";
        }

        if ($results['oppla_delivery_zones'] > 0) {
            $parts[] = "{$results['oppla_delivery_zones']} zone consegna";
        }

        if ($results['oppla_managed_deliveries'] > 0) {
            $parts[] = "{$results['oppla_managed_deliveries']} consegne gestite";
        }

        if (empty($parts)) {
            return 'Nessun dato da sincronizzare';
        }

        $message = 'Sincronizzati: ' . implode(', ', $parts);
        
        if (!empty($results['errors'])) {
            $message .= ' (con alcuni errori)';
        }

        return $message;
    }

    private function mapOpplaOrderStatus($status)
    {
        $mapping = [
            'new' => 'pending',
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
        ];

        return $mapping[$status] ?? 'pending';
    }

    /**
     * Test della connessione PostgreSQL
     */
    public function testPostgreSQLConnection(Request $request)
    {
        $results = [
            'pdo_pgsql_loaded' => extension_loaded('pdo_pgsql'),
            'pgsql_loaded' => extension_loaded('pgsql'),
            'connection_configured' => config('database.connections.oppla_readonly') !== null,
            'connection_test' => null,
            'error' => null,
        ];

        try {
            // Tenta connessione
            $pdo = \DB::connection('oppla_readonly')->getPdo();
            $results['connection_test'] = 'success';
            
            // Test query
            $users = \DB::connection('oppla_readonly')
                ->table('users')
                ->limit(1)
                ->get();
            
            $results['query_test'] = 'success';
            $results['sample_record'] = $users->first();
            
        } catch (\Exception $e) {
            $results['connection_test'] = 'failed';
            $results['error'] = $e->getMessage();
        }

        return response()->json($results);
    }
}
