<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Client;

class OpplaSyncInitial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oppla:sync-initial 
                            {--orders : Sincronizza solo gli ordini}
                            {--deliveries : Sincronizza solo le consegne}
                            {--partners : Sincronizza solo i partner}
                            {--all : Sincronizza tutto (default)}
                            {--month= : Filtra per mese specifico (formato: YYYY-MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizzazione iniziale di tutti i dati dal database OPPLA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Avvio sincronizzazione iniziale OPPLA...');
        $this->newLine();

        $syncAll = $this->option('all') || (!$this->option('orders') && !$this->option('deliveries') && !$this->option('partners'));

        // Test connessione database Oppla
        try {
            DB::connection('oppla_pgsql')->getPdo();
            $this->info('Connessione al database OPPLA stabilita');
        } catch (\Exception $e) {
            $this->error('❌ Errore connessione database OPPLA: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();

        // Sincronizza Partners
        if ($syncAll || $this->option('partners')) {
            $this->syncPartners();
        }

        // Sincronizza Ordini
        if ($syncAll || $this->option('orders')) {
            $this->syncOrders();
        }

        // Sincronizza Deliveries
        if ($syncAll || $this->option('deliveries')) {
            $this->syncDeliveries();
        }

        $this->newLine();
        $this->info('Sincronizzazione iniziale completata con successo!');

        return 0;
    }

    /**
     * Sincronizza i partner dal database OPPLA
     */
    protected function syncPartners()
    {
        $this->info('📦 Sincronizzazione Partner...');

        try {
            $partners = DB::connection('oppla_pgsql')
                ->table('users')
                ->where('type', 'partner')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $bar = $this->output->createProgressBar($partners->count());
            $bar->start();

            $imported = 0;
            foreach ($partners as $partner) {
                Client::updateOrCreate(
                    ['oppla_external_id' => $partner->id],
                    [
                        'ragione_sociale' => $partner->business_name ?? $partner->name ?? 'Partner ' . $partner->id,
                        'tipo_cliente' => 'Partner OPPLA',
                        'email' => $partner->email ?? null,
                        'telefono' => $partner->phone ?? null,
                        'indirizzo' => $partner->address ?? null,
                        'citta' => $partner->city ?? null,
                        'provincia' => $partner->province ?? null,
                        'cap' => $partner->zip_code ?? null,
                        'partita_iva' => $partner->vat_number ?? null,
                        'codice_fiscale' => $partner->tax_code ?? null,
                        'oppla_data' => json_decode(json_encode($partner), true),
                    ]
                );
                $imported++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("   Partner importati: {$imported}/{$partners->count()}");
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('   ❌ Errore sincronizzazione partner: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Sincronizza gli ordini dal database OPPLA
     */
    protected function syncOrders()
    {
        $this->info('📦 Sincronizzazione Ordini...');

        try {
            $query = DB::connection('oppla_pgsql')
                ->table('orders');
            
            // Filtra per mese se specificato
            if ($month = $this->option('month')) {
                $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
                $query->whereBetween('date', [$startDate, $endDate]);
                $this->info("   Filtrando ordini del mese: {$month}");
            }
            
            $orders = $query->orderBy('date', 'desc')->get();

            $bar = $this->output->createProgressBar($orders->count());
            $bar->start();

            $imported = 0;
            foreach ($orders as $opplaOrder) {
                // Determina il client_id locale tramite oppla_external_id
                $localClientId = null;

                if (isset($opplaOrder->user_id) && $opplaOrder->user_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->user_id)->first();
                    if ($client) $localClientId = $client->id;
                }

                if (!$localClientId && isset($opplaOrder->customer_id) && $opplaOrder->customer_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->customer_id)->first();
                    if ($client) $localClientId = $client->id;
                }

                if (!$localClientId && isset($opplaOrder->restaurant_id) && $opplaOrder->restaurant_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaOrder->restaurant_id)->first();
                    if ($client) $localClientId = $client->id;
                }

                Order::updateOrCreate(
                    ['oppla_order_id' => $opplaOrder->id],
                    [
                        'order_number' => $opplaOrder->order_number ?? $opplaOrder->number ?? 'ORD-' . $opplaOrder->id,
                        'client_id' => $localClientId,
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
                $imported++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("   Ordini importati: {$imported}/{$orders->count()}");
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('   ❌ Errore sincronizzazione ordini: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Sincronizza le consegne dal database OPPLA
     */
    protected function syncDeliveries()
    {
        $this->info('📦 Sincronizzazione Consegne...');

        try {
            $deliveries = DB::connection('oppla_pgsql')
                ->table('managed_deliveries')
                ->orderBy('created_at', 'desc')
                ->get();

            $bar = $this->output->createProgressBar($deliveries->count());
            $bar->start();

            $imported = 0;
            foreach ($deliveries as $opplaDelivery) {
                // Determina il client_id locale tramite oppla_external_id
                $localClientId = null;

                if (isset($opplaDelivery->partner_id) && $opplaDelivery->partner_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->partner_id)->first();
                    if ($client) $localClientId = $client->id;
                }

                if (!$localClientId && isset($opplaDelivery->user_id) && $opplaDelivery->user_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->user_id)->first();
                    if ($client) $localClientId = $client->id;
                }

                if (!$localClientId && isset($opplaDelivery->customer_id) && $opplaDelivery->customer_id) {
                    $client = DB::table('clients')->where('oppla_external_id', $opplaDelivery->customer_id)->first();
                    if ($client) $localClientId = $client->id;
                }

                DB::table('deliveries')->updateOrInsert(
                    ['order_id' => $opplaDelivery->delivery_code ?? $opplaDelivery->id ?? 'DEL-' . uniqid()],
                    [
                        'client_id' => $localClientId,
                        'order_type' => 'card',
                        'pickup_address' => $opplaDelivery->pickup_address ?? $opplaDelivery->restaurant_address ?? '',
                        'delivery_address' => $opplaDelivery->delivery_address ?? $opplaDelivery->shipping_address ?? '',
                        'order_date' => $opplaDelivery->created_at ?? now(),
                        'pickup_time' => $opplaDelivery->picked_up_at ?? null,
                        'delivery_time' => $opplaDelivery->delivered_at ?? null,
                        'distance_km' => $opplaDelivery->distance_km ?? 0,
                        'order_amount' => $opplaDelivery->total_amount ?? 0,
                        'delivery_fee_base' => $opplaDelivery->delivery_fee ?? 0,
                        'delivery_fee_total' => $opplaDelivery->delivery_fee ?? 0,
                        'status' => 'delivered',
                        'note' => $opplaDelivery->notes ?? null,
                        'created_at' => $opplaDelivery->created_at ?? now(),
                        'updated_at' => $opplaDelivery->updated_at ?? now(),
                    ]
                );
                $imported++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("   Consegne importate: {$imported}/{$deliveries->count()}");
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('   ❌ Errore sincronizzazione consegne: ' . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Estrae la città da un indirizzo
     */
    protected function extractCity($address)
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
