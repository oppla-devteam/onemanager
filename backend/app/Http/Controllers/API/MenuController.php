<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\MenuImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    protected $importService;

    public function __construct(MenuImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display a listing of menu items
     * GET /api/menus?restaurant_id=1&category=BIRRE&search=corona
     */
    public function index(Request $request)
    {
        try {
            $query = MenuItem::with('restaurant:id,nome,citta')
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('product_name');

            // Filter by restaurant
            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('product_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            // Filter by availability
            if ($request->has('available_for_delivery') && $request->available_for_delivery) {
                $query->where('available_for_delivery', true);
            }
            if ($request->has('available_for_pickup') && $request->available_for_pickup) {
                $query->where('available_for_pickup', true);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Pagination
            $perPage = $request->get('per_page', 50);
            $items = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $items->items(),
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ]);

        } catch (\Exception $e) {
            Log::error('[Menu] Error fetching menu items: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching menu items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created menu item
     * POST /api/menus
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'category' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_cents' => 'required|integer|min:0',
            'available_for_delivery' => 'nullable|boolean',
            'available_for_pickup' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'image_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $menuItem = MenuItem::create($request->all());

            Log::info('[Menu] Menu item created', [
                'id' => $menuItem->id,
                'restaurant_id' => $menuItem->restaurant_id,
                'product_name' => $menuItem->product_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully',
                'data' => $menuItem->load('restaurant:id,nome'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('[Menu] Error creating menu item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error creating menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified menu item
     * GET /api/menus/{id}
     */
    public function show($id)
    {
        try {
            $menuItem = MenuItem::with('restaurant:id,nome,citta')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $menuItem,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Menu item not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified menu item
     * PUT /api/menus/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price_cents' => 'nullable|integer|min:0',
            'available_for_delivery' => 'nullable|boolean',
            'available_for_pickup' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'image_url' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $menuItem = MenuItem::findOrFail($id);
            $menuItem->update($request->all());

            Log::info('[Menu] Menu item updated', [
                'id' => $menuItem->id,
                'product_name' => $menuItem->product_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully',
                'data' => $menuItem->load('restaurant:id,nome'),
            ]);

        } catch (\Exception $e) {
            Log::error('[Menu] Error updating menu item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error updating menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified menu item (soft delete)
     * DELETE /api/menus/{id}
     */
    public function destroy($id)
    {
        try {
            $menuItem = MenuItem::findOrFail($id);
            $menuItem->delete();

            Log::info('[Menu] Menu item deleted', [
                'id' => $id,
                'product_name' => $menuItem->product_name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Menu item deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('[Menu] Error deleting menu item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error deleting menu item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import menu items from CSV
     * POST /api/menus/import
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $restaurantId = $request->restaurant_id;
            $userId = auth()->id();

            // Store file temporarily
            $path = $file->storeAs('imports', 'menu_' . $restaurantId . '_' . time() . '.csv');
            $fullPath = storage_path('app/' . $path);

            // Import
            $results = $this->importService->importFromCsvFile($restaurantId, $fullPath, $userId);

            // Delete temporary file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Menu import completed',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('[Menu] CSV import error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error importing menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export menu items to CSV
     * GET /api/menus/export?restaurant_id=1
     */
    public function exportCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $restaurantId = $request->restaurant_id;
            $restaurant = Restaurant::findOrFail($restaurantId);

            // Get all menu items for the restaurant
            $menuItems = MenuItem::where('restaurant_id', $restaurantId)
                ->orderBy('category')
                ->orderBy('sort_order')
                ->orderBy('product_name')
                ->get();

            // Generate CSV content
            $csv = "category,product_name,price_cents,description,available_for_delivery,available_for_pickup,image_url\n";

            foreach ($menuItems as $item) {
                $csv .= sprintf(
                    '"%s","%s",%d,"%s",%s,%s,"%s"' . "\n",
                    str_replace('"', '""', $item->category),
                    str_replace('"', '""', $item->product_name),
                    $item->price_cents,
                    str_replace('"', '""', $item->description ?? ''),
                    $item->available_for_delivery ? 'TRUE' : 'FALSE',
                    $item->available_for_pickup ? 'TRUE' : 'FALSE',
                    $item->image_url ?? ''
                );
            }

            $filename = 'menu_' . $restaurant->nome . '_' . date('Y-m-d_H-i-s') . '.csv';

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('[Menu] CSV export error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for a restaurant
     * GET /api/menus/categories?restaurant_id=1
     */
    public function getCategories(Request $request)
    {
        try {
            $query = MenuItem::select('category')
                ->distinct()
                ->orderBy('category');

            if ($request->has('restaurant_id')) {
                $query->where('restaurant_id', $request->restaurant_id);
            }

            $categories = $query->pluck('category');

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import history for a restaurant
     * GET /api/menus/import-history?restaurant_id=1
     */
    public function getImportHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $history = $this->importService->getImportHistory($request->restaurant_id);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching import history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update menu items
     * POST /api/menus/bulk-update
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:menu_items,id',
            'updates' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updated = MenuItem::whereIn('id', $request->ids)
                ->update($request->updates);

            Log::info('[Menu] Bulk update completed', [
                'count' => $updated,
                'updates' => $request->updates,
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} menu items updated",
                'count' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('[Menu] Bulk update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error bulk updating menu items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
