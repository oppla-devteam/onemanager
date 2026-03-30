<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\CustomerOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerOrderController extends Controller
{
    public function __construct(
        private CustomerOrderService $orderService
    ) {}

    /**
     * Browse available shops/activities.
     * Reads from OPPLA restaurants + enriches with local data.
     */
    public function browseShops(Request $request)
    {
        try {
            $opplaRestaurants = DB::connection('oppla')
                ->table('restaurants')
                ->select('id', 'name', 'slug', 'address', 'phone', 'email')
                ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
                ->when($request->city, fn ($q, $c) => $q->where('address', 'ilike', "%{$c}%"))
                ->orderBy('name')
                ->get();

            $enriched = $opplaRestaurants->map(function ($r) {
                $local = Restaurant::where('oppla_external_id', $r->id)->first();
                $menuCount = $local
                    ? MenuItem::where('restaurant_id', $local->id)->active()->count()
                    : 0;

                return [
                    'id' => $r->id,
                    'local_id' => $local?->id,
                    'name' => $r->name,
                    'address' => $r->address,
                    'phone' => $r->phone,
                    'category' => $local?->category,
                    'city' => $local?->citta,
                    'has_menu' => $menuCount > 0,
                    'menu_items_count' => $menuCount,
                    'is_active' => $local?->is_active ?? true,
                ];
            });

            // Filter by category (local data)
            if ($request->category) {
                $enriched = $enriched->filter(
                    fn ($r) => stripos($r['category'] ?? '', $request->category) !== false
                );
            }

            // Only show active shops with menus
            $enriched = $enriched->filter(fn ($r) => $r['is_active'] && $r['has_menu']);

            return response()->json([
                'success' => true,
                'data' => $enriched->values(),
                'total' => $enriched->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('[CustomerOrder] Browse shops error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching shops: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * View menu/catalog for a specific shop.
     * $restaurantId is the OPPLA restaurant ID.
     */
    public function viewMenu(Request $request, $restaurantId)
    {
        $local = Restaurant::where('oppla_external_id', $restaurantId)->first();

        if (! $local) {
            return response()->json([
                'success' => false,
                'message' => 'Shop not found or has no menu configured.',
            ], 404);
        }

        $query = MenuItem::where('restaurant_id', $local->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('product_name');

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->delivery_type === 'delivery') {
            $query->where('available_for_delivery', true);
        } elseif ($request->delivery_type === 'pickup') {
            $query->where('available_for_pickup', true);
        }

        $items = $query->get();

        $grouped = $items->groupBy('category')->map(fn ($catItems, $cat) => [
            'category' => $cat,
            'items' => $catItems->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->product_name,
                'description' => $i->description,
                'price_cents' => $i->price_cents,
                'price_display' => $i->formatted_price,
                'available_for_delivery' => $i->available_for_delivery,
                'available_for_pickup' => $i->available_for_pickup,
                'image_url' => $i->image_url,
            ])->values(),
        ])->values();

        return response()->json([
            'success' => true,
            'restaurant' => [
                'id' => $restaurantId,
                'name' => $local->nome,
                'address' => $local->indirizzo,
                'city' => $local->citta,
                'category' => $local->category,
            ],
            'categories' => $grouped,
            'total_items' => $items->count(),
        ]);
    }

    /**
     * Place an order. Writes to OPPLA PostgreSQL + creates local record.
     */
    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|integer|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
            'delivery_type' => 'required|in:delivery,pickup',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'delivery_address' => 'required_if:delivery_type,delivery|nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->orderService->createOrder($validated);

            return response()->json([
                'success' => true,
                'message' => "Order placed successfully at {$result['restaurant_name']}",
                'data' => [
                    'order_number' => $result['order_number'],
                    'oppla_order_id' => $result['oppla_order_id'],
                    'local_order_id' => $result['order']->id,
                    'restaurant' => $result['restaurant_name'],
                    'items' => $result['items'],
                    'subtotal_cents' => $result['subtotal'],
                    'delivery_fee_cents' => $result['delivery_fee'],
                    'total_cents' => $result['total'],
                    'total_display' => '€'.number_format($result['total'] / 100, 2, ',', '.'),
                    'status' => 'pending',
                    'delivery_type' => $validated['delivery_type'],
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'customer_name' => $validated['customer_name'],
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[CustomerOrder] Place order error', [
                'error' => $e->getMessage(),
                'restaurant_id' => $validated['restaurant_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error placing order: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track an order by order_number or oppla_order_id.
     * Reads from OPPLA for real-time status, enriches with local item data.
     */
    public function trackOrder(Request $request)
    {
        $request->validate([
            'order_number' => 'required_without:oppla_order_id|nullable|string',
            'oppla_order_id' => 'required_without:order_number|nullable',
        ]);

        try {
            // Try OPPLA first for real-time status
            $opplaOrder = null;

            if ($request->oppla_order_id) {
                $opplaOrder = DB::connection('oppla')->table('orders')
                    ->where('id', $request->oppla_order_id)
                    ->first();
            } elseif ($request->order_number) {
                $opplaOrder = DB::connection('oppla')->table('orders')
                    ->where('order_number', $request->order_number)
                    ->first();
            }

            // Enrich with local data (items JSON)
            $localOrder = null;

            if ($opplaOrder) {
                $localOrder = Order::where('oppla_order_id', $opplaOrder->id)->first();
            } elseif ($request->order_number) {
                $localOrder = Order::where('order_number', $request->order_number)->first();
            }

            if (! $opplaOrder && ! $localOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => $opplaOrder->order_number ?? $localOrder->order_number,
                    'status' => $opplaOrder->status ?? $localOrder->status,
                    'restaurant_id' => $opplaOrder->restaurant_id ?? $localOrder->restaurant_id,
                    'customer_name' => $opplaOrder->customer_name ?? $localOrder->customer_name,
                    'delivery_address' => $opplaOrder->delivery_address ?? $localOrder->shipping_address,
                    'delivery_type' => $opplaOrder->type ?? $localOrder->delivery_type,
                    'subtotal_cents' => (int) ($opplaOrder->subtotal ?? $localOrder->subtotal),
                    'delivery_fee_cents' => (int) ($opplaOrder->delivery_fee ?? $localOrder->delivery_fee),
                    'total_cents' => (int) ($opplaOrder->total ?? $localOrder->total_amount),
                    'total_display' => '€'.number_format(
                        ($opplaOrder->total ?? $localOrder->total_amount) / 100,
                        2, ',', '.'
                    ),
                    'items' => $localOrder?->items ?? [],
                    'items_count' => (int) ($opplaOrder->items_count ?? $localOrder->items_count),
                    'created_at' => $opplaOrder->created_at ?? $localOrder->created_at,
                    'updated_at' => $opplaOrder->updated_at ?? $localOrder->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[CustomerOrder] Track order error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error tracking order: '.$e->getMessage(),
            ], 500);
        }
    }
}
