<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Support\Facades\Log;

class ClientObserver
{
    public function __construct(
        private ContractService $contractService
    ) {}

    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        // Skip if client was created via onboarding (contract already generated)
        if (request()->routeIs('api.onboarding.*')) {
            Log::info('[ClientObserver] Skipping contract generation - client created via onboarding', [
                'client_id' => $client->id,
            ]);
            return;
        }

        // Skip if client already has a contract
        if ($client->contracts()->exists()) {
            Log::info('[ClientObserver] Skipping contract generation - client already has contracts', [
                'client_id' => $client->id,
                'contract_count' => $client->contracts()->count(),
            ]);
            return;
        }

        // Skip if client type is not partner_oppla
        if ($client->type !== 'partner_oppla') {
            Log::info('[ClientObserver] Skipping contract generation - client type is not partner_oppla', [
                'client_id' => $client->id,
                'type' => $client->type,
            ]);
            return;
        }

        // Wait a moment for restaurant to be created (if creating via UI)
        // This is necessary because restaurant might be created right after client
        sleep(2);

        // Generate contract automatically
        try {
            // Get first restaurant (if exists)
            $restaurant = $client->restaurants()->first();

            if (!$restaurant) {
                Log::warning('[ClientObserver] No restaurant found for client, skipping contract generation', [
                    'client_id' => $client->id,
                ]);
                return;
            }

            $restaurantData = [
                'nome' => $restaurant->nome,
                'indirizzo' => ($restaurant->indirizzo ?? '') . ', ' .
                              ($restaurant->citta ?? '') . ' ' .
                              ($restaurant->provincia ?? ''),
                'best_price' => false,
            ];

            // Use default fee structure
            $feeData = [
                'activation_fee' => 150.00,
                'pickup_fee' => 12.00,
                'main_service_fee' => 2.98,
                'rejected_order_fee' => 1.49,
                'manual_entry_fee' => 1.49,
                'delivery_subscription' => 24.00,
                'equipment_provided' => true,
            ];

            $contract = $this->contractService->createFromOnboarding(
                $client->id,
                $restaurantData,
                $feeData
            );

            Log::info('[ClientObserver] Contract auto-generated for manually created client', [
                'client_id' => $client->id,
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'restaurant' => $restaurant->nome,
            ]);

        } catch (\Exception $e) {
            Log::error('[ClientObserver] Failed to auto-generate contract', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
