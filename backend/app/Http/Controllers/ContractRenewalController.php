<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractRenewalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractRenewalController extends Controller
{
    public function __construct(
        private ContractRenewalService $renewalService
    ) {}

    /**
     * Get renewal statistics
     */
    public function stats()
    {
        return response()->json($this->renewalService->getStats());
    }

    /**
     * Get contracts expiring soon
     */
    public function expiring(Request $request)
    {
        $days = $request->input('days', 30);
        $contracts = $this->renewalService->getExpiringContracts($days);

        return response()->json([
            'data' => $contracts,
            'total' => $contracts->count(),
            'days' => $days,
        ]);
    }

    /**
     * Get expired contracts not yet handled
     */
    public function expired()
    {
        $contracts = $this->renewalService->getExpiredContracts();

        return response()->json([
            'data' => $contracts,
            'total' => $contracts->count(),
        ]);
    }

    /**
     * Manually renew a contract
     */
    public function manualRenew(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'duration_months' => 'nullable|integer|min:1|max:120',
            'start_date' => 'nullable|date',
            'value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,attivo',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $newContract = $this->renewalService->manualRenew($contract, $request->all());

            return response()->json([
                'message' => 'Contratto rinnovato con successo',
                'old_contract' => $contract->fresh(),
                'new_contract' => $newContract->load(['client', 'creator']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore durante il rinnovo del contratto',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel auto-renewal for a contract
     */
    public function cancelAutoRenew(Request $request, Contract $contract)
    {
        if (!$contract->auto_renew) {
            return response()->json([
                'message' => 'Il rinnovo automatico è già disabilitato',
            ]);
        }

        $this->renewalService->cancelAutoRenewal($contract, $request->input('reason'));

        return response()->json([
            'message' => 'Rinnovo automatico disabilitato',
            'contract' => $contract->fresh(),
        ]);
    }

    /**
     * Enable auto-renewal for a contract
     */
    public function enableAutoRenew(Request $request, Contract $contract)
    {
        if ($contract->auto_renew) {
            return response()->json([
                'message' => 'Il rinnovo automatico è già abilitato',
            ]);
        }

        $contract->update(['auto_renew' => true]);

        $contract->history()->create([
            'user_id' => auth()->id(),
            'action' => 'auto_renewal_enabled',
            'notes' => 'Rinnovo automatico abilitato',
        ]);

        return response()->json([
            'message' => 'Rinnovo automatico abilitato',
            'contract' => $contract->fresh(),
        ]);
    }

    /**
     * Process renewals manually (admin trigger)
     */
    public function processRenewals(Request $request)
    {
        $notifyOnly = $request->boolean('notify_only', false);
        $renewOnly = $request->boolean('renew_only', false);

        $stats = [
            'notified' => 0,
            'renewed' => 0,
            'errors' => [],
        ];

        // Process notifications
        if (!$renewOnly) {
            $contractsToNotify = $this->renewalService->getContractsToNotify();
            
            foreach ($contractsToNotify as $contract) {
                try {
                    $this->renewalService->sendExpirationNotification($contract);
                    $stats['notified']++;
                } catch (\Exception $e) {
                    $stats['errors'][] = [
                        'contract_id' => $contract->id,
                        'action' => 'notify',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        // Process auto-renewals
        if (!$notifyOnly) {
            $contractsToRenew = $this->renewalService->getContractsToAutoRenew();
            
            foreach ($contractsToRenew as $contract) {
                try {
                    $this->renewalService->renewContract($contract);
                    $stats['renewed']++;
                } catch (\Exception $e) {
                    $stats['errors'][] = [
                        'contract_id' => $contract->id,
                        'action' => 'renew',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return response()->json([
            'message' => 'Elaborazione rinnovi completata',
            'stats' => $stats,
        ]);
    }
}
