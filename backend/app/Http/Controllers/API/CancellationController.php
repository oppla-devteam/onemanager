<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CancellationController extends Controller
{
    public function __construct(
        protected CancellationService $cancellationService
    ) {}

    /**
     * Preview cancellation across all systems (local, Oppla, Tookan)
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:order,delivery',
            'id' => 'required|integer',
        ]);

        try {
            $result = $this->cancellationService->preview(
                $request->input('type'),
                $request->input('id')
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() === 404 ? 404 : 500);
        }
    }

    /**
     * Execute confirmed cancellation
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation_token' => 'required|string',
        ]);

        try {
            $result = $this->cancellationService->execute(
                $request->input('confirmation_token')
            );

            $statusCode = $result['overall_success'] ? 200 : 207; // 207 = Multi-Status (partial)

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
