<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('client');

        // Filtro per data personalizzata (priorità alta)
        if ($request->has('start_date') || $request->has('end_date')) {
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('order_date', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            } elseif ($request->has('start_date')) {
                $query->where('order_date', '>=', $request->start_date . ' 00:00:00');
            } elseif ($request->has('end_date')) {
                $query->where('order_date', '<=', $request->end_date . ' 23:59:59');
            }
        }
        // Filtro per periodo (solo se non ci sono date personalizzate)
        elseif ($request->has('period')) {
            switch ($request->period) {
                case 'all':
                    // Nessun filtro di data, mostra tutti gli ordini
                    break;
                case 'today':
                    $query->whereDate('order_date', today());
                    break;
                case 'week':
                    $query->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('order_date', now()->month)
                          ->whereYear('order_date', now()->year);
                    break;
                case 'year':
                    $query->whereYear('order_date', now()->year);
                    break;
                case 'last_month':
                    $query->whereBetween('order_date', [
                        now()->subMonth()->startOfMonth(),
                        now()->subMonth()->endOfMonth()
                    ]);
                    break;
                case 'last_year':
                    $query->where('order_date', '>=', now()->subYear());
                    break;
            }
        }

        // Filtro per status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro per cliente
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Se viene richiesto 'all', restituisce tutti i risultati senza paginazione
        if ($request->get('all') === 'true' || $request->get('all') === true) {
            $orders = $query->orderBy('order_date', 'desc')->get();
        } else {
            $orders = $query->orderBy('order_date', 'desc')->paginate(20);
        }

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function stats(Request $request)
    {
        $period = $request->get('period', 'year'); // today, week, month, year

        $query = Order::query();

        // Applica filtro periodo
        switch ($period) {
            case 'all':
                // Nessun filtro di data, mostra tutti gli ordini
                break;
            case 'today':
                $query->whereDate('order_date', today());
                break;
            case 'week':
                $query->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('order_date', now()->month)
                      ->whereYear('order_date', now()->year);
                break;
            case 'year':
                $query->whereYear('order_date', now()->year);
                break;
            case 'last_month':
                $query->whereBetween('order_date', [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]);
                break;
            case 'last_year':
                $query->where('order_date', '>=', now()->subYear());
                break;
        }

        // Statistiche generali
        $totalOrders = (clone $query)->count();
        $totalRevenue = (clone $query)->sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Ordini per status
        $ordersByStatus = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Distribuzione geografica (per provincia)
        $ordersByProvince = (clone $query)
            ->select('shipping_province', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as revenue'))
            ->whereNotNull('shipping_province')
            ->groupBy('shipping_province')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Distribuzione geografica (per città)
        $ordersByCity = (clone $query)
            ->select('shipping_city', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as revenue'))
            ->whereNotNull('shipping_city')
            ->groupBy('shipping_city')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Ordini per mese (ultimi 12 mesi)
        // Use DB-agnostic date extraction (works with both MySQL and SQLite)
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $yearExpr = "strftime('%Y', order_date)";
            $monthExpr = "cast(strftime('%m', order_date) as integer)";
        } else {
            $yearExpr = 'YEAR(order_date)';
            $monthExpr = 'MONTH(order_date)';
        }

        $monthlyOrders = Order::query()
            ->select(
                DB::raw("{$yearExpr} as year"),
                DB::raw("{$monthExpr} as month"),
                DB::raw('count(*) as count'),
                DB::raw('sum(total_amount) as revenue')
            )
            ->where('order_date', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Top clienti per numero ordini
        $topClientsByOrders = (clone $query)
            ->select('client_id', DB::raw('count(*) as orders_count'), DB::raw('sum(total_amount) as total_spent'))
            ->groupBy('client_id')
            ->orderBy('orders_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $client = Client::find($item->client_id);
                return [
                    'client' => $client ? [
                        'id' => $client->id,
                        'name' => $client->ragione_sociale,
                        'city' => $client->citta,
                    ] : null,
                    'orders_count' => $item->orders_count,
                    'total_spent' => $item->total_spent,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_orders' => $totalOrders,
                'total_revenue' => round($totalRevenue, 2),
                'average_order_value' => round($averageOrderValue, 2),
                'orders_by_status' => $ordersByStatus,
                'orders_by_province' => $ordersByProvince,
                'orders_by_city' => $ordersByCity,
                'monthly_orders' => $monthlyOrders,
                'top_clients' => $topClientsByOrders,
            ]
        ]);
    }

    public function show($id)
    {
        $order = Order::with('client', 'invoice')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Sincronizza ordini e consegne dal database Oppla
     */
    public function sync(Request $request)
    {
        try {
            $ordersImported = 0;
            $deliveriesImported = 0;

            // Test connessione database Oppla
            DB::connection('oppla_pgsql')->getPdo();

            $force = $request->input('force', false); // Parametro per forzare sync completo

            // SYNC INCREMENTALE: Trova ultimo ordine sincronizzato (ignorato se force=true)
            $lastSyncedOrder = $force ? null : Order::orderBy('oppla_sync_at', 'desc')->first();
            
            // Se non ci sono ordini sincronizzati O se force=true, importa TUTTO
            if (!$lastSyncedOrder) {
                // PRIMA VOLTA o FORZA SYNC: importa TUTTI gli ordini senza filtro di data
                $opplaOrders = DB::connection('oppla_pgsql')
                    ->table('orders')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // SYNC INCREMENTALE: Sincronizza solo ordini NUOVI dall'ultimo sync
                $lastSyncDate = $lastSyncedOrder->oppla_sync_at;

                $query = DB::connection('oppla_pgsql')->table('orders');
                if ($lastSyncDate) {
                    $query->where('created_at', '>', $lastSyncDate);
                }
                $opplaOrders = $query->orderBy('created_at', 'desc')->get();
            }

            foreach ($opplaOrders as $opplaOrder) {
                // Determina il client_id locale tramite oppla_external_id (opzionale)
                $localClientId = null;
                
                // Prova con user_id
                if (!$localClientId && isset($opplaOrder->user_id) && $opplaOrder->user_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->user_id)->first();
                    if ($client) {
                        $localClientId = $client->id;
                    }
                }
                
                // Prova con customer_id
                if (!$localClientId && isset($opplaOrder->customer_id) && $opplaOrder->customer_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->customer_id)->first();
                    if ($client) {
                        $localClientId = $client->id;
                    }
                }
                
                // Prova con restaurant_id
                if (!$localClientId && isset($opplaOrder->restaurant_id) && $opplaOrder->restaurant_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->restaurant_id)->first();
                    if ($client) {
                        $localClientId = $client->id;
                    }
                }

                Order::updateOrCreate(
                    ['order_number' => $opplaOrder->order_number ?? 'ORD-' . $opplaOrder->id],
                    [
                        'client_id' => $localClientId,
                        'oppla_order_id' => $opplaOrder->id ?? null,
                        'restaurant_id' => $opplaOrder->restaurant_id ?? null,
                        // CRITICAL: Usa date/original_date (data originale ordine), non created_at (data import)
                        'order_date' => $opplaOrder->original_date ?? $opplaOrder->date ?? $opplaOrder->created_at ?? now(),
                        // CRITICAL: Gli importi sono in centesimi nella tabella orders esterna
                        'subtotal' => isset($opplaOrder->subtotal) ? (int) $opplaOrder->subtotal : 0,
                        'delivery_fee' => isset($opplaOrder->delivery_fee) ? (int) $opplaOrder->delivery_fee : 0,
                        'discount' => isset($opplaOrder->discount) ? (int) $opplaOrder->discount : 0,
                        'total_amount' => isset($opplaOrder->total) ? (int) $opplaOrder->total : 0, // Campo "total" non "total_amount"
                        'currency' => 'EUR',
                        'status' => $opplaOrder->status ?? 'pending',
                        'delivery_type' => $opplaOrder->type ?? 'Delivery', // Campo "type" non "delivery_type"
                        'items_count' => $opplaOrder->items_count ?? 0,
                        'customer_name' => $opplaOrder->customer_name ?? null,
                        'shipping_address' => $opplaOrder->delivery_address ?? '',
                        'shipping_city' => $this->extractCity($opplaOrder->delivery_address ?? ''),
                        'is_invoiced' => false,
                        'oppla_sync_at' => now(),
                        // Salva TUTTI i dati in oppla_data (inclusi stripe_fee_id, platform_fee_id, has_platform_discount)
                        'oppla_data' => json_decode(json_encode($opplaOrder), true),
                    ]
                );
                $ordersImported++;
            }

            // SYNC INCREMENTALE: Trova ultima delivery sincronizzata (ignorato se force=true)
            $lastSyncedDelivery = $force ? null : DB::table('deliveries')->orderBy('updated_at', 'desc')->first();

            // Importa deliveries
            if (!$lastSyncedDelivery) {
                // PRIMA VOLTA o FORZA SYNC: importa TUTTE le deliveries senza filtro di data
                $opplaDeliveries = DB::connection('oppla_pgsql')
                    ->table('managed_deliveries')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // Sincronizza solo deliveries NUOVE dall'ultimo sync
                $lastDeliverySync = $lastSyncedDelivery->updated_at;

                $query = DB::connection('oppla_pgsql')->table('managed_deliveries');
                if ($lastDeliverySync) {
                    $query->where('created_at', '>', $lastDeliverySync);
                }
                $opplaDeliveries = $query->orderBy('created_at', 'desc')->get();
            }

            foreach ($opplaDeliveries as $opplaDelivery) {
                // Determina il client_id locale tramite oppla_external_id (opzionale)
                $localClientId = null;
                
                // Prova con partner_id per primo (più comune per managed_deliveries)
                if (!$localClientId && isset($opplaDelivery->partner_id) && $opplaDelivery->partner_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->partner_id)->first();
                    if ($client) {
                        $localClientId = $client->id;
                    }
                }
                
                // Prova con user_id
                if (!$localClientId && isset($opplaDelivery->user_id) && $opplaDelivery->user_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->user_id)->first();
                    if ($client) {
                        $localClientId = $client->id;
                    }
                }
                
                // Prova con customer_id
                if (!$localClientId && isset($opplaDelivery->customer_id) && $opplaDelivery->customer_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->customer_id)->first();
                    if ($client) {
                        $localClientId = $client->id;
                    }
                }

                // MAPPA TUTTI I CAMPI DA MANAGED_DELIVERIES
                DB::table('deliveries')->updateOrInsert(
                    ['order_id' => $opplaDelivery->delivery_code ?? $opplaDelivery->id ?? 'DEL-' . uniqid()],
                    [
                        // Campi esistenti
                        'client_id' => $localClientId,
                        'order_type' => strtolower($opplaDelivery->payment_method ?? 'card'),
                        'pickup_address' => $opplaDelivery->pickup_address ?? $opplaDelivery->restaurant_address ?? '',
                        'delivery_address' => $opplaDelivery->delivery_address ?? $opplaDelivery->shipping_address ?? '',
                        'order_date' => $opplaDelivery->delivery_date ?? $opplaDelivery->created_at ?? now(),
                        'pickup_time' => $opplaDelivery->picked_up_at ?? null,
                        'delivery_time' => $opplaDelivery->delivered_at ?? null,
                        'distance_km' => $opplaDelivery->distance_km ?? 0,
                        'order_amount' => $opplaDelivery->order_amount ?? $opplaDelivery->total_amount ?? 0,
                        'delivery_fee_base' => $opplaDelivery->delivery_fee_base ?? $opplaDelivery->delivery_fee ?? 0,
                        'delivery_fee_total' => $opplaDelivery->delivery_fee_total ?? $opplaDelivery->delivery_fee ?? 0,
                        'status' => $opplaDelivery->status ?? 'Created',
                        'note' => $opplaDelivery->notes ?? $opplaDelivery->note ?? null,
                        'created_at' => $opplaDelivery->created_at ?? now(),
                        'updated_at' => $opplaDelivery->updated_at ?? now(),
                        
                        // NUOVI CAMPI DA MANAGED_DELIVERIES
                        'oppla_id' => $opplaDelivery->id ?? null,
                        'partner_id' => $opplaDelivery->partner_id ?? null,
                        'restaurant_id' => $opplaDelivery->restaurant_id ?? null,
                        'user_id' => $opplaDelivery->user_id ?? null,
                        'delivery_code' => $opplaDelivery->delivery_code ?? null,
                        'delivery_scheduled_at' => $opplaDelivery->delivery_date ?? null,
                        'shipping_address' => $opplaDelivery->delivery_address ?? null,
                        'gps_location' => $opplaDelivery->gps_location ?? null,
                        'delivery_notes' => $opplaDelivery->delivery_notes ?? null,
                        'customer_name' => $opplaDelivery->customer_name ?? null,
                        'customer_phone' => $opplaDelivery->customer_phone ?? null,
                        'original_amount' => $opplaDelivery->order_amount ?? null,
                        'payment_method' => $opplaDelivery->payment_method ?? null,
                        'platform_fee' => $opplaDelivery->platform_fee ?? null,
                        'distance_fee' => $opplaDelivery->distance_fee ?? null,
                        'platform_fee_id' => $opplaDelivery->platform_fee_id ?? null,
                        'distance_fee_id' => $opplaDelivery->distance_fee_id ?? null,
                        'payment_intent' => $opplaDelivery->payment_intent ?? null,
                        'oppla_created_at' => $opplaDelivery->created_at ?? null,
                        'oppla_updated_at' => $opplaDelivery->updated_at ?? null,
                    ]
                );
                $deliveriesImported++;
            }
            
            return response()->json([
                'success' => true,
                'message' => $force ? 'Forza sincronizzazione completata con successo' : 'Sincronizzazione completata con successo',
                'data' => [
                    'orders_imported' => $ordersImported,
                    'orders_found' => $opplaOrders->count(),
                    'deliveries_imported' => $deliveriesImported,
                    'deliveries_found' => $opplaDeliveries->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estrae la città da un indirizzo
     */
    private function extractCity($address)
    {
        if (empty($address)) {
            return null;
        }

        // Pattern comuni per indirizzi italiani: "Via XXX, Città, Provincia, Italia"
        $parts = explode(',', $address);
        
        if (count($parts) >= 2) {
            // Prende la penultima parte (di solito è la città)
            $city = trim($parts[count($parts) - 2]);
            
            // Rimuovi codici postali e province (es. "Livorno LI" -> "Livorno")
            $city = preg_replace('/\s+[A-Z]{2}\s*$/', '', $city);
            $city = preg_replace('/\s+\d{5}\s*$/', '', $city);
            
            return $city;
        }

        return null;
    }

    /**
     * Esporta ordini e/o deliveries in formato CSV
     */
    public function export(Request $request)
    {
        // Raccogli i dati da esportare (applica gli stessi filtri dell'index)
        $data = [];
        // Supporta sia 'source' che 'data_source' per compatibilità
        $source = $request->get('data_source', $request->get('source', 'all')); // 'all', 'orders', 'deliveries'

        // Carica ordini se richiesto
        if ($source === 'all' || $source === 'orders') {
            $ordersQuery = Order::with('client');
            
            // Applica filtri (stesso codice dell'index)
            if ($request->has('start_date') || $request->has('end_date')) {
                if ($request->has('start_date') && $request->has('end_date')) {
                    $ordersQuery->whereBetween('order_date', [
                        $request->start_date . ' 00:00:00',
                        $request->end_date . ' 23:59:59'
                    ]);
                } elseif ($request->has('start_date')) {
                    $ordersQuery->where('order_date', '>=', $request->start_date . ' 00:00:00');
                } elseif ($request->has('end_date')) {
                    $ordersQuery->where('order_date', '<=', $request->end_date . ' 23:59:59');
                }
            } elseif ($request->has('period') && $request->period !== 'all') {
                switch ($request->period) {
                    case 'today':
                        $ordersQuery->whereDate('order_date', today());
                        break;
                    case 'week':
                        $ordersQuery->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $ordersQuery->whereMonth('order_date', now()->month)
                              ->whereYear('order_date', now()->year);
                        break;
                    case 'year':
                        $ordersQuery->whereYear('order_date', now()->year);
                        break;
                    case 'last_month':
                        $ordersQuery->whereBetween('order_date', [
                            now()->subMonth()->startOfMonth(),
                            now()->subMonth()->endOfMonth()
                        ]);
                        break;
                    case 'last_year':
                        $ordersQuery->where('order_date', '>=', now()->subYear());
                        break;
                }
            }

            if ($request->has('status')) {
                $ordersQuery->where('status', $request->status);
            }

            if ($request->has('client_id')) {
                $ordersQuery->where('client_id', $request->client_id);
            }

            if ($request->has('restaurant_id')) {
                $ordersQuery->where('restaurant_id', $request->restaurant_id);
            }

            $orders = $ordersQuery->orderBy('order_date', 'desc')->get();
            
            foreach ($orders as $order) {
                $data[] = [
                    'Tipo' => 'Ordine',
                    'ID' => $order->id,
                    'Numero Ordine' => $order->order_number,
                    'Data' => $order->order_date,
                    'Cliente' => $order->client?->ragione_sociale ?? 'N/A',
                    'Ristorante ID' => $order->restaurant_id,
                    'Nome Cliente' => $order->customer_name,
                    'Indirizzo' => $order->shipping_address,
                    'Città' => $order->shipping_city,
                    'Provincia' => $order->shipping_province ?? '',
                    'Stato' => $order->status,
                    'Subtotale (€)' => number_format($order->subtotal / 100, 2, ',', '.'),
                    'Costo Consegna (€)' => number_format($order->delivery_fee / 100, 2, ',', '.'),
                    'Sconto (€)' => number_format($order->discount / 100, 2, ',', '.'),
                    'Totale (€)' => number_format($order->total_amount / 100, 2, ',', '.'),
                    'Articoli' => $order->items_count ?? 0,
                ];
            }
        }

        // Carica deliveries se richiesto
        if ($source === 'all' || $source === 'deliveries') {
            $deliveriesQuery = \App\Models\Delivery::with('client');
            
            // Applica filtri
            if ($request->has('start_date') || $request->has('end_date')) {
                if ($request->has('start_date') && $request->has('end_date')) {
                    $deliveriesQuery->whereBetween('order_date', [
                        $request->start_date . ' 00:00:00',
                        $request->end_date . ' 23:59:59'
                    ]);
                } elseif ($request->has('start_date')) {
                    $deliveriesQuery->where('order_date', '>=', $request->start_date . ' 00:00:00');
                } elseif ($request->has('end_date')) {
                    $deliveriesQuery->where('order_date', '<=', $request->end_date . ' 23:59:59');
                }
            } elseif ($request->has('period') && $request->period !== 'all') {
                switch ($request->period) {
                    case 'today':
                        $deliveriesQuery->whereDate('order_date', today());
                        break;
                    case 'week':
                        $deliveriesQuery->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $deliveriesQuery->whereMonth('order_date', now()->month)
                              ->whereYear('order_date', now()->year);
                        break;
                    case 'year':
                        $deliveriesQuery->whereYear('order_date', now()->year);
                        break;
                    case 'last_month':
                        $deliveriesQuery->whereBetween('order_date', [
                            now()->subMonth()->startOfMonth(),
                            now()->subMonth()->endOfMonth()
                        ]);
                        break;
                    case 'last_year':
                        $deliveriesQuery->where('order_date', '>=', now()->subYear());
                        break;
                }
            }

            if ($request->has('status')) {
                $deliveriesQuery->where('status', $request->status);
            }

            if ($request->has('client_id')) {
                $deliveriesQuery->where('client_id', $request->client_id);
            }

            $deliveries = $deliveriesQuery->orderBy('order_date', 'desc')->get();
            
            foreach ($deliveries as $delivery) {
                $data[] = [
                    'Tipo' => 'Consegna Gestita',
                    'ID' => $delivery->id,
                    'Numero Ordine' => $delivery->order_id ?? 'N/A',
                    'Data' => $delivery->order_date ?? $delivery->created_at,
                    'Cliente' => $delivery->client?->ragione_sociale ?? 'N/A',
                    'Ristorante ID' => '',
                    'Nome Cliente' => $delivery->client?->ragione_sociale ?? 'N/A',
                    'Indirizzo' => $delivery->delivery_address,
                    'Città' => '',
                    'Provincia' => '',
                    'Stato' => $delivery->status,
                    'Subtotale (€)' => number_format(($delivery->delivery_fee_total ?? 0) / 100, 2, ',', '.'),
                    'Costo Consegna (€)' => number_format(($delivery->delivery_fee_total ?? 0) / 100, 2, ',', '.'),
                    'Sconto (€)' => '0,00',
                    'Totale (€)' => number_format(($delivery->order_amount ?? 0) / 100, 2, ',', '.'),
                    'Articoli' => 0,
                ];
            }
        }

        // Crea CSV
        $filename = 'ordini_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 per Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header CSV
            if (!empty($data)) {
                fputcsv($file, array_keys($data[0]), ';');
                
                // Righe dati
                foreach ($data as $row) {
                    fputcsv($file, $row, ';');
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete order and propagate changes (remove from Stripe, FIC, invoices, etc.)
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $order = Order::findOrFail($id);
            
            // Log operazione
            \Log::info('Deleting order with propagation', [
                'order_id' => $id,
                'order_number' => $order->order_number,
                'client_id' => $order->client_id
            ]);
            
            // 1. Rimuovi riferimenti dalle fatture
            if ($order->invoice_id) {
                $invoice = \App\Models\Invoice::find($order->invoice_id);
                if ($invoice) {
                    // Rimuovi order_id dalle invoice_items
                    \App\Models\InvoiceItem::where('invoice_id', $invoice->id)
                        ->where('order_id', $order->id)
                        ->delete();
                    
                    // Ricalcola totale fattura
                    $invoice->recalculateTotals();
                    
                    \Log::info('Removed order from invoice', ['invoice_id' => $invoice->id]);
                }
            }
            
            // 2. Rimuovi da Stripe dashboard (se necessario)
            if ($order->oppla_data && isset($order->oppla_data['payment_intent'])) {
                \Log::info('Order has Stripe payment_intent', [
                    'payment_intent' => $order->oppla_data['payment_intent']
                ]);
                // Nota: Non eliminiamo il pagamento da Stripe, solo il riferimento
            }
            
            // 3. Elimina l'ordine
            $order->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Ordine eliminato con successo'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting order', [
                'order_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }
}
