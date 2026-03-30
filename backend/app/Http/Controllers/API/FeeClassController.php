<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FeeClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeeClassController extends Controller
{
    /**
     * Get all fee classes
     */
    public function index()
    {
        $feeClasses = FeeClass::with('restaurants')->get();

        return response()->json([
            'success' => true,
            'data' => $feeClasses,
        ]);
    }

    /**
     * Create new fee class
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'delivery_type' => 'required|in:autonomous,managed',
            'best_price' => 'required|boolean',
            'monthly_fee' => 'nullable|numeric|min:0',
            'order_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'order_fee_fixed' => 'nullable|numeric|min:0',
            'delivery_base_fee' => 'nullable|numeric|min:0',
            'delivery_km_fee' => 'nullable|numeric|min:0',
            'payment_processing_fee' => 'nullable|numeric|min:0|max:100',
            'platform_fee' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $feeClass = FeeClass::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Classe fee creata con successo',
                'data' => $feeClass,
            ], 201);

        } catch (\Exception $e) {
            Log::error('[FeeClass] Errore creazione: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single fee class
     */
    public function show($id)
    {
        $feeClass = FeeClass::with('restaurants')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $feeClass,
        ]);
    }

    /**
     * Update fee class
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'delivery_type' => 'sometimes|in:autonomous,managed',
            'best_price' => 'sometimes|boolean',
            'monthly_fee' => 'nullable|numeric|min:0',
            'order_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'order_fee_fixed' => 'nullable|numeric|min:0',
            'delivery_base_fee' => 'nullable|numeric|min:0',
            'delivery_km_fee' => 'nullable|numeric|min:0',
            'payment_processing_fee' => 'nullable|numeric|min:0|max:100',
            'platform_fee' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $feeClass = FeeClass::findOrFail($id);
            $feeClass->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Classe fee aggiornata con successo',
                'data' => $feeClass,
            ]);

        } catch (\Exception $e) {
            Log::error('[FeeClass] Errore aggiornamento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete fee class
     */
    public function destroy($id)
    {
        try {
            $feeClass = FeeClass::findOrFail($id);
            
            // Check if used by restaurants
            if ($feeClass->restaurants()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile eliminare: classe fee in uso da ristoranti',
                ], 422);
            }

            $feeClass->delete();

            return response()->json([
                'success' => true,
                'message' => 'Classe fee eliminata con successo',
            ]);

        } catch (\Exception $e) {
            Log::error('[FeeClass] Errore eliminazione: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get fee class for specific configuration
     */
    public function getForConfiguration(Request $request)
    {
        $validated = $request->validate([
            'delivery_type' => 'required|in:autonomous,managed',
            'best_price' => 'required|boolean',
        ]);

        $feeClass = FeeClass::where('delivery_type', $validated['delivery_type'])
            ->where('best_price', $validated['best_price'])
            ->where('is_active', true)
            ->first();

        if (!$feeClass) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna classe fee trovata per questa configurazione',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $feeClass,
        ]);
    }
}
