<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerOrderService
{
    /**
     * Resolve menu items, validate availability, and calculate prices.
     *
     * @return array{items: array, subtotal: int, items_count: int}
     */
    public function resolveItems(int $localRestaurantId, array $items, string $deliveryType): array
    {
        $orderItems = [];
        $subtotal = 0;
        $itemsCount = 0;

        foreach ($items as $item) {
            $menuItem = MenuItem::where('id', $item['menu_item_id'])
                ->where('restaurant_id', $localRestaurantId)
                ->where('is_active', true)
                ->first();

            if (!$menuItem) {
                throw new \InvalidArgumentException(
                    "Menu item #{$item['menu_item_id']} not found or inactive for this shop."
                );
            }

            if ($deliveryType === 'delivery' && !$menuItem->available_for_delivery) {
                throw new \InvalidArgumentException(
                    "'{$menuItem->product_name}' is not available for delivery."
                );
            }

            if ($deliveryType === 'pickup' && !$menuItem->available_for_pickup) {
                throw new \InvalidArgumentException(
                    "'{$menuItem->product_name}' is not available for pickup."
                );
            }

            $lineTotal = $menuItem->price_cents * $item['quantity'];
            $subtotal += $lineTotal;
            $itemsCount += $item['quantity'];

            $orderItems[] = [
                'menu_item_id' => $menuItem->id,
                'product_name' => $menuItem->product_name,
                'category' => $menuItem->category,
                'price_cents' => $menuItem->price_cents,
                'quantity' => $item['quantity'],
                'line_total_cents' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        return [
            'items' => $orderItems,
            'subtotal' => $subtotal,
            'items_count' => $itemsCount,
        ];
    }

    /**
     * Calculate delivery fee for a restaurant.
     * Returns fee in cents. Returns 0 for pickup.
     */
    public function calculateDeliveryFee(Restaurant $restaurant, string $deliveryType): int
    {
        if ($deliveryType === 'pickup') {
            return 0;
        }

        // Use fee from client's per-order delivery fee if available
        $client = $restaurant->client;
        if ($client && $client->fee_consegna_base) {
            return (int) ($client->fee_consegna_base * 100);
        }

        // Default delivery fee: 250 cents = €2.50
        return 250;
    }

    /**
     * Create an order in OPPLA and locally.
     *
     * @return array{order: Order, oppla_order_id: string, order_number: string}
     */
    public function createOrder(array $data): array
    {
        $restaurant = Restaurant::where('oppla_external_id', $data['restaurant_id'])->first();

        if (!$restaurant) {
            throw new \InvalidArgumentException('Shop not found. Verify the restaurant_id is a valid OPPLA ID.');
        }

        // Resolve items and calculate prices
        $resolved = $this->resolveItems(
            $restaurant->id,
            $data['items'],
            $data['delivery_type']
        );

        $deliveryFee = $this->calculateDeliveryFee($restaurant, $data['delivery_type']);
        $total = $resolved['subtotal'] + $deliveryFee;
        $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        $now = now()->utc();

        // Write to OPPLA PostgreSQL
        DB::connection('oppla')->table('orders')->insert([
            'restaurant_id' => $data['restaurant_id'],
            'order_number' => $orderNumber,
            'status' => 'pending',
            'type' => ucfirst($data['delivery_type']),
            'original_date' => $now,
            'date' => $now,
            'created_at' => $now,
            'updated_at' => $now,
            'subtotal' => $resolved['subtotal'],
            'delivery_fee' => $deliveryFee,
            'discount' => 0,
            'total' => $total,
            'delivery_address' => $data['delivery_address'] ?? null,
            'customer_name' => $data['customer_name'],
            'items_count' => $resolved['items_count'],
        ]);

        // Get the inserted order ID from OPPLA
        $opplaOrder = DB::connection('oppla')->table('orders')
            ->where('order_number', $orderNumber)
            ->first();

        $opplaOrderId = $opplaOrder->id ?? null;

        // Create local order record
        $localOrder = Order::create([
            'oppla_order_id' => $opplaOrderId,
            'restaurant_id' => $data['restaurant_id'],
            'client_id' => $restaurant->client_id,
            'order_number' => $orderNumber,
            'customer_name' => $data['customer_name'],
            'order_date' => $now,
            'subtotal' => $resolved['subtotal'],
            'delivery_fee' => $deliveryFee,
            'discount' => 0,
            'total_amount' => $total,
            'currency' => 'EUR',
            'status' => 'pending',
            'delivery_type' => ucfirst($data['delivery_type']),
            'items' => $resolved['items'],
            'items_count' => $resolved['items_count'],
            'shipping_address' => $data['delivery_address'] ?? '',
            'is_invoiced' => false,
            'oppla_sync_at' => now(),
            'oppla_data' => [
                'source' => 'mcp_customer_order',
                'notes' => $data['notes'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
            ],
        ]);

        Log::info('[CustomerOrder] Order placed', [
            'oppla_id' => $opplaOrderId,
            'order_number' => $orderNumber,
            'restaurant' => $restaurant->nome,
            'total_cents' => $total,
            'items_count' => $resolved['items_count'],
        ]);

        return [
            'order' => $localOrder,
            'oppla_order_id' => $opplaOrderId,
            'order_number' => $orderNumber,
            'restaurant_name' => $restaurant->nome,
            'items' => $resolved['items'],
            'subtotal' => $resolved['subtotal'],
            'delivery_fee' => $deliveryFee,
            'total' => $total,
        ];
    }
}
