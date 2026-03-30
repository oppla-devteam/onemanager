<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\OpplaWriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class OpplaWriteController extends Controller
{
    protected OpplaWriteService $writeService;

    public function __construct(OpplaWriteService $writeService)
    {
        $this->writeService = $writeService;
    }

    /**
     * Request confirmation for a write operation
     * POST /api/oppla/write/request-confirmation
     */
    public function requestConfirmation(Request $request)
    {
        $validated = $request->validate([
            'operation' => 'required|in:INSERT,UPDATE,DELETE',
            'table' => 'required|string',
            'data' => 'required|array',
            'conditions' => 'nullable|array'
        ]);

        try {
            $result = $this->writeService->requestConfirmation(
                $validated['operation'],
                $validated['table'],
                $validated['data'],
                $validated['conditions'] ?? null
            );

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('[OpplaWrite] Request confirmation failed', [
                'error' => $e->getMessage(),
                'request' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare operation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute operation with confirmation token
     * POST /api/oppla/write/execute
     */
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string'
        ]);

        try {
            $result = $this->writeService->executeWithConfirmation($validated['token']);

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('[OpplaWrite] Execution failed', [
                'error' => $e->getMessage(),
                'token' => substr($validated['token'], 0, 16) . '...'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute operation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Example: Update restaurant status
     * POST /api/oppla/restaurants/{id}/update
     */
    public function updateRestaurant(Request $request, int $id)
    {
        $validated = $request->validate([
            'is_active' => 'nullable|boolean',
            'nome' => 'nullable|string',
            'indirizzo' => 'nullable|string',
            'telefono' => 'nullable|string',
        ]);

        // Filter out null values
        $data = array_filter($validated, fn($value) => $value !== null);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No data provided for update'
            ], 400);
        }

        try {
            return response()->json(
                $this->writeService->requestConfirmation(
                    'UPDATE',
                    'restaurants',
                    $data,
                    ['id' => $id]
                )
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare restaurant update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new partner
     * POST /api/oppla/partners/create
     */
    public function createPartner(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
        ]);

        try {
            return response()->json(
                $this->writeService->requestConfirmation(
                    'INSERT',
                    'partners',
                    $validated,
                    null
                )
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare partner creation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update partner
     * POST /api/oppla/partners/{id}/update
     */
    public function updatePartner(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $data = array_filter($validated, fn($value) => $value !== null);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No data provided for update'
            ], 400);
        }

        try {
            return response()->json(
                $this->writeService->requestConfirmation(
                    'UPDATE',
                    'partners',
                    $data,
                    ['id' => $id]
                )
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare partner update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     * POST /api/oppla/orders/{id}/update-status
     */
    public function updateOrderStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        try {
            return response()->json(
                $this->writeService->requestConfirmation(
                    'UPDATE',
                    'orders',
                    ['status' => $validated['status']],
                    ['id' => $id]
                )
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare order status update',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete order
     * DELETE /api/oppla/orders/{id}
     */
    public function deleteOrder(int $id)
    {
        try {
            return response()->json(
                $this->writeService->requestConfirmation(
                    'DELETE',
                    'orders',
                    [],
                    ['id' => $id]
                )
            );
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to prepare order deletion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
