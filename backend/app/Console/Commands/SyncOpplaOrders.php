<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOpplaOrders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'oppla:sync-orders
                            {--force : Force full sync, ignoring last sync timestamp}
                            {--source=scheduler : Data source identifier (scheduler, manual)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync orders and deliveries from OPPLA PostgreSQL database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $source = $this->option('source');
        $force = $this->option('force');

        $this->info("🚀 Starting OPPLA orders sync (source: {$source})...");

        try {
            // Test connection to OPPLA database
            $this->info('📡 Testing OPPLA database connection...');
            DB::connection('oppla_pgsql')->getPdo();
            $this->info('✅ OPPLA database connected');

            $ordersImported = 0;
            $deliveriesImported = 0;

            // === SYNC ORDERS ===
            $this->info('📦 Syncing orders...');

            $lastSyncedOrder = $force ? null : Order::orderBy('oppla_sync_at', 'desc')->first();

            if (!$lastSyncedOrder) {
                $this->info('   Full sync mode - importing all orders');
                $opplaOrders = DB::connection('oppla_pgsql')
                    ->table('orders')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $lastSyncDate = $lastSyncedOrder->oppla_sync_at;
                $this->info("   Incremental sync since: {$lastSyncDate}");

                $opplaOrders = DB::connection('oppla_pgsql')
                    ->table('orders')
                    ->where('created_at', '>', $lastSyncDate)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            $this->info("   Found " . $opplaOrders->count() . " orders to import");

            $progressBar = $this->output->createProgressBar($opplaOrders->count());
            $progressBar->start();

            foreach ($opplaOrders as $opplaOrder) {
                $localClientId = $this->findLocalClient($opplaOrder);

                Order::updateOrCreate(
                    ['order_number' => $opplaOrder->order_number ?? 'ORD-' . $opplaOrder->id],
                    [
                        'client_id' => $localClientId,
                        'oppla_order_id' => $opplaOrder->id ?? null,
                        'restaurant_id' => $opplaOrder->restaurant_id ?? null,
                        'order_date' => $opplaOrder->original_date ?? $opplaOrder->date ?? $opplaOrder->created_at ?? now(),
                        'subtotal' => isset($opplaOrder->subtotal) ? (int) $opplaOrder->subtotal : 0,
                        'delivery_fee' => isset($opplaOrder->delivery_fee) ? (int) $opplaOrder->delivery_fee : 0,
                        'discount' => isset($opplaOrder->discount) ? (int) $opplaOrder->discount : 0,
                        'total_amount' => isset($opplaOrder->total) ? (int) $opplaOrder->total : 0,
                        'currency' => 'EUR',
                        'status' => $opplaOrder->status ?? 'pending',
                        'delivery_type' => $opplaOrder->type ?? 'Delivery',
                        'items_count' => $opplaOrder->items_count ?? 0,
                        'customer_name' => $opplaOrder->customer_name ?? null,
                        'shipping_address' => $opplaOrder->delivery_address ?? '',
                        'shipping_city' => $this->extractCity($opplaOrder->delivery_address ?? ''),
                        'is_invoiced' => false,
                        'oppla_sync_at' => now(),
                        'oppla_data' => json_decode(json_encode($opplaOrder), true),
                    ]
                );
                $ordersImported++;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // === SYNC DELIVERIES ===
            $this->info('🚚 Syncing deliveries...');

            $lastSyncedDelivery = $force ? null : DB::table('deliveries')->orderBy('updated_at', 'desc')->first();

            if (!$lastSyncedDelivery) {
                $this->info('   Full sync mode - importing all deliveries');
                $opplaDeliveries = DB::connection('oppla_pgsql')
                    ->table('managed_deliveries')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $lastDeliverySync = $lastSyncedDelivery->updated_at;
                $this->info("   Incremental sync since: {$lastDeliverySync}");

                $opplaDeliveries = DB::connection('oppla_pgsql')
                    ->table('managed_deliveries')
                    ->where('created_at', '>', $lastDeliverySync)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            $this->info("   Found " . $opplaDeliveries->count() . " deliveries to import");

            $progressBar = $this->output->createProgressBar($opplaDeliveries->count());
            $progressBar->start();

            foreach ($opplaDeliveries as $opplaDelivery) {
                $localClientId = $this->findLocalClientForDelivery($opplaDelivery);

                DB::table('deliveries')->updateOrInsert(
                    ['order_id' => $opplaDelivery->delivery_code ?? $opplaDelivery->id ?? 'DEL-' . uniqid()],
                    [
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
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log results
            $this->newLine();
            $this->info('📊 Sync Statistics:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Orders Found', $opplaOrders->count()],
                    ['Orders Imported', $ordersImported],
                    ['Deliveries Found', $opplaDeliveries->count()],
                    ['Deliveries Imported', $deliveriesImported],
                ]
            );
            $this->info("⏱️  Execution time: {$executionTime}ms");
            $this->info('✅ OPPLA sync completed successfully!');

            Log::info('[OpplaSync] Orders sync completed', [
                'source' => $source,
                'orders_imported' => $ordersImported,
                'deliveries_imported' => $deliveriesImported,
                'forced' => $force,
                'execution_time_ms' => $executionTime,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Sync failed: " . $e->getMessage());
            Log::error('[OpplaSync] Orders sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'source' => $source,
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Find local client ID by oppla_external_id for orders
     */
    private function findLocalClient($opplaOrder): ?int
    {
        // Try user_id
        if (isset($opplaOrder->user_id) && $opplaOrder->user_id) {
            $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->user_id)->first();
            if ($client) return $client->id;
        }

        // Try customer_id
        if (isset($opplaOrder->customer_id) && $opplaOrder->customer_id) {
            $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->customer_id)->first();
            if ($client) return $client->id;
        }

        // Try restaurant_id
        if (isset($opplaOrder->restaurant_id) && $opplaOrder->restaurant_id) {
            $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->restaurant_id)->first();
            if ($client) return $client->id;
        }

        return null;
    }

    /**
     * Find local client ID by oppla_external_id for deliveries
     */
    private function findLocalClientForDelivery($opplaDelivery): ?int
    {
        // Try partner_id first (most common for managed_deliveries)
        if (isset($opplaDelivery->partner_id) && $opplaDelivery->partner_id) {
            $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->partner_id)->first();
            if ($client) return $client->id;
        }

        // Try user_id
        if (isset($opplaDelivery->user_id) && $opplaDelivery->user_id) {
            $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->user_id)->first();
            if ($client) return $client->id;
        }

        // Try customer_id
        if (isset($opplaDelivery->customer_id) && $opplaDelivery->customer_id) {
            $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->customer_id)->first();
            if ($client) return $client->id;
        }

        return null;
    }

    /**
     * Extract city from address
     */
    private function extractCity($address): ?string
    {
        if (empty($address)) {
            return null;
        }

        $parts = explode(',', $address);

        if (count($parts) >= 2) {
            $city = trim($parts[count($parts) - 2]);
            $city = preg_replace('/\s+[A-Z]{2}\s*$/', '', $city);
            $city = preg_replace('/\s+\d{5}\s*$/', '', $city);
            return $city;
        }

        return null;
    }
}
