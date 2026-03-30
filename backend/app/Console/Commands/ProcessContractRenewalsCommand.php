<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\ContractRenewalService;
use Illuminate\Console\Command;

class ProcessContractRenewalsCommand extends Command
{
    protected $signature = 'contracts:process-renewals 
                            {--notify-only : Only send expiration notifications, skip auto-renewals}
                            {--renew-only : Only process auto-renewals, skip notifications}
                            {--days=30 : Default days before expiration to notify}';

    protected $description = 'Process contract renewals and expiration notifications';

    public function handle(ContractRenewalService $renewalService)
    {
        $this->info('Starting contract renewal processing...');
        
        $notifyOnly = $this->option('notify-only');
        $renewOnly = $this->option('renew-only');
        $defaultDays = (int) $this->option('days');

        $stats = [
            'notified' => 0,
            'renewed' => 0,
            'errors' => 0,
        ];

        // 1. Process expiration notifications
        if (!$renewOnly) {
            $this->info('Processing expiration notifications...');
            
            $contractsToNotify = $renewalService->getContractsToNotify($defaultDays);
            
            $this->info("Found {$contractsToNotify->count()} contracts needing notification");
            
            foreach ($contractsToNotify as $contract) {
                try {
                    $renewalService->sendExpirationNotification($contract);
                    $stats['notified']++;
                    $this->line("  ✓ Notified: {$contract->contract_number} - {$contract->client_name}");
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->error("  ✗ Error notifying {$contract->contract_number}: {$e->getMessage()}");
                }
            }
        }

        // 2. Process auto-renewals
        if (!$notifyOnly) {
            $this->info('Processing auto-renewals...');
            
            $contractsToRenew = $renewalService->getContractsToAutoRenew();
            
            $this->info("Found {$contractsToRenew->count()} contracts for auto-renewal");
            
            foreach ($contractsToRenew as $contract) {
                try {
                    $newContract = $renewalService->renewContract($contract);
                    $stats['renewed']++;
                    $this->line("  ✓ Renewed: {$contract->contract_number} → {$newContract->contract_number}");
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->error("  ✗ Error renewing {$contract->contract_number}: {$e->getMessage()}");
                }
            }
        }

        // Summary
        $this->newLine();
        $this->info('=== Contract Renewal Summary ===');
        $this->info("Notifications sent: {$stats['notified']}");
        $this->info("Contracts renewed: {$stats['renewed']}");
        
        if ($stats['errors'] > 0) {
            $this->error("Errors: {$stats['errors']}");
        }

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
