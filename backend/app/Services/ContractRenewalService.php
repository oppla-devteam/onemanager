<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractHistory;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use App\Notifications\ContractRenewedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ContractRenewalService
{
    /**
     * Get contracts that need expiration notification
     */
    public function getContractsToNotify(int $defaultDays = 30): Collection
    {
        return Contract::query()
            ->whereIn('status', ['active', 'attivo', 'signed'])
            ->where('notify_expiration', true)
            ->whereNotNull('expires_at')
            // Check if we're within notification window
            ->where(function ($query) use ($defaultDays) {
                $query->whereRaw('expires_at <= DATE_ADD(NOW(), INTERVAL COALESCE(notify_days_before, ?) DAY)', [$defaultDays])
                      ->whereRaw('expires_at > NOW()'); // Not yet expired
            })
            // Don't notify if already notified recently (within last 7 days)
            ->where(function ($query) {
                $query->whereDoesntHave('history', function ($q) {
                    $q->where('action', 'expiration_notification_sent')
                      ->where('created_at', '>', now()->subDays(7));
                });
            })
            ->with(['client', 'creator', 'assignee'])
            ->get();
    }

    /**
     * Get contracts ready for auto-renewal
     * Contracts that are expiring soon and have auto_renew enabled
     */
    public function getContractsToAutoRenew(): Collection
    {
        return Contract::query()
            ->whereIn('status', ['active', 'attivo', 'signed'])
            ->where('auto_renew', true)
            ->whereNotNull('expires_at')
            // Renew contracts expiring within next 3 days
            ->whereBetween('expires_at', [now(), now()->addDays(3)])
            // Not already renewed
            ->where(function ($query) {
                $query->whereDoesntHave('history', function ($q) {
                    $q->where('action', 'auto_renewed');
                });
            })
            ->with(['client', 'creator'])
            ->get();
    }

    /**
     * Get contracts expiring in the next N days (for reporting)
     */
    public function getExpiringContracts(int $days = 30): Collection
    {
        return Contract::query()
            ->whereIn('status', ['active', 'attivo', 'signed'])
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->with(['client', 'creator', 'assignee'])
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Get expired contracts (not yet handled)
     */
    public function getExpiredContracts(): Collection
    {
        return Contract::query()
            ->whereIn('status', ['active', 'attivo', 'signed'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with(['client'])
            ->get();
    }

    /**
     * Send expiration notification for a contract
     */
    public function sendExpirationNotification(Contract $contract): void
    {
        $daysUntilExpiry = now()->diffInDays($contract->expires_at, false);

        // Notify assignee and creator
        $usersToNotify = collect();
        
        if ($contract->assignee) {
            $usersToNotify->push($contract->assignee);
        }
        if ($contract->creator && (!$contract->assignee || $contract->creator->id !== $contract->assignee->id)) {
            $usersToNotify->push($contract->creator);
        }
        
        // Fallback to admin users if no specific assignee
        if ($usersToNotify->isEmpty()) {
            $usersToNotify = User::role('admin')->take(3)->get();
        }

        // Send notification
        foreach ($usersToNotify as $user) {
            try {
                $this->sendExpirationEmail($user, $contract, $daysUntilExpiry);
            } catch (\Exception $e) {
                Log::error("Failed to send contract expiration notification", [
                    'contract_id' => $contract->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Also notify client if email available
        if ($contract->client && $contract->client->email) {
            try {
                $this->sendClientExpirationEmail($contract, $daysUntilExpiry);
            } catch (\Exception $e) {
                Log::warning("Failed to send client contract expiration notification", [
                    'contract_id' => $contract->id,
                    'client_email' => $contract->client->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log the notification
        $contract->history()->create([
            'action' => 'expiration_notification_sent',
            'new_status' => $contract->status,
            'notes' => "Notifica scadenza inviata. Giorni rimanenti: {$daysUntilExpiry}",
        ]);

        Log::info("Contract expiration notification sent", [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'expires_at' => $contract->expires_at,
            'days_until_expiry' => $daysUntilExpiry,
        ]);
    }

    /**
     * Renew a contract automatically
     */
    public function renewContract(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            // Calculate new dates
            $durationMonths = $contract->duration_months ?? $contract->periodo_mesi ?? 12;
            $newStartDate = $contract->expires_at ?? $contract->end_date ?? now();
            $newEndDate = $newStartDate->copy()->addMonths($durationMonths);

            // Create new contract as copy
            $newContract = $contract->replicate([
                'id',
                'contract_number',
                'created_at',
                'updated_at',
                'sent_at',
                'signed_at',
                'activated_at',
                'signature_token',
                'signature_token_expires_at',
            ]);

            $newContract->contract_number = Contract::generateContractNumber();
            $newContract->start_date = $newStartDate;
            $newContract->end_date = $newEndDate;
            $newContract->expires_at = $newEndDate;
            $newContract->status = 'active';
            $newContract->version = ($contract->version ?? 1) + 1;
            $newContract->notes = "Rinnovo automatico del contratto {$contract->contract_number}";
            $newContract->save();

            // Mark old contract as renewed
            $contract->update([
                'status' => 'renewed',
                'auto_renew' => false, // Prevent duplicate renewals
            ]);

            // Log history on old contract
            $contract->history()->create([
                'action' => 'auto_renewed',
                'old_status' => 'active',
                'new_status' => 'renewed',
                'notes' => "Contratto rinnovato automaticamente. Nuovo contratto: {$newContract->contract_number}",
            ]);

            // Log history on new contract
            $newContract->history()->create([
                'action' => 'created_from_renewal',
                'new_status' => 'active',
                'notes' => "Creato da rinnovo automatico del contratto {$contract->contract_number}",
            ]);

            // Send renewal confirmation emails
            $this->sendRenewalNotification($contract, $newContract);

            Log::info("Contract auto-renewed", [
                'old_contract_id' => $contract->id,
                'old_contract_number' => $contract->contract_number,
                'new_contract_id' => $newContract->id,
                'new_contract_number' => $newContract->contract_number,
                'new_end_date' => $newEndDate,
            ]);

            return $newContract;
        });
    }

    /**
     * Manually renew a contract
     */
    public function manualRenew(Contract $contract, array $overrides = []): Contract
    {
        $durationMonths = $overrides['duration_months'] ?? $contract->duration_months ?? 12;
        $newStartDate = isset($overrides['start_date']) 
            ? \Carbon\Carbon::parse($overrides['start_date']) 
            : ($contract->expires_at ?? $contract->end_date ?? now());
        $newEndDate = $newStartDate->copy()->addMonths($durationMonths);

        return DB::transaction(function () use ($contract, $overrides, $newStartDate, $newEndDate, $durationMonths) {
            $newContract = $contract->replicate([
                'id',
                'contract_number',
                'created_at',
                'updated_at',
                'sent_at',
                'signed_at',
                'activated_at',
                'signature_token',
                'signature_token_expires_at',
            ]);

            $newContract->contract_number = Contract::generateContractNumber();
            $newContract->start_date = $newStartDate;
            $newContract->end_date = $newEndDate;
            $newContract->expires_at = $newEndDate;
            $newContract->duration_months = $durationMonths;
            $newContract->status = $overrides['status'] ?? 'draft';
            $newContract->version = ($contract->version ?? 1) + 1;
            $newContract->value = $overrides['value'] ?? $contract->value;
            $newContract->notes = $overrides['notes'] ?? "Rinnovo del contratto {$contract->contract_number}";
            $newContract->created_by = auth()->id() ?? $contract->created_by;
            $newContract->save();

            // Mark old contract
            $contract->update(['status' => 'renewed']);

            // Log history
            $contract->history()->create([
                'user_id' => auth()->id(),
                'action' => 'manually_renewed',
                'old_status' => $contract->status,
                'new_status' => 'renewed',
                'notes' => "Contratto rinnovato manualmente. Nuovo contratto: {$newContract->contract_number}",
            ]);

            $newContract->history()->create([
                'user_id' => auth()->id(),
                'action' => 'created_from_manual_renewal',
                'new_status' => $newContract->status,
                'notes' => "Creato da rinnovo manuale del contratto {$contract->contract_number}",
            ]);

            return $newContract;
        });
    }

    /**
     * Cancel auto-renewal for a contract
     */
    public function cancelAutoRenewal(Contract $contract, ?string $reason = null): void
    {
        $contract->update(['auto_renew' => false]);
        
        $contract->history()->create([
            'user_id' => auth()->id(),
            'action' => 'auto_renewal_cancelled',
            'notes' => $reason ?? 'Rinnovo automatico disabilitato',
        ]);

        Log::info("Contract auto-renewal cancelled", [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'reason' => $reason,
        ]);
    }

    /**
     * Get renewal statistics
     */
    public function getStats(): array
    {
        $now = now();
        
        return [
            'expiring_7_days' => Contract::whereIn('status', ['active', 'attivo', 'signed'])
                ->whereBetween('expires_at', [$now, $now->copy()->addDays(7)])
                ->count(),
            'expiring_30_days' => Contract::whereIn('status', ['active', 'attivo', 'signed'])
                ->whereBetween('expires_at', [$now, $now->copy()->addDays(30)])
                ->count(),
            'expiring_90_days' => Contract::whereIn('status', ['active', 'attivo', 'signed'])
                ->whereBetween('expires_at', [$now, $now->copy()->addDays(90)])
                ->count(),
            'expired_not_handled' => Contract::whereIn('status', ['active', 'attivo', 'signed'])
                ->where('expires_at', '<', $now)
                ->count(),
            'auto_renew_enabled' => Contract::whereIn('status', ['active', 'attivo', 'signed'])
                ->where('auto_renew', true)
                ->count(),
            'renewed_this_month' => Contract::where('status', 'renewed')
                ->whereMonth('updated_at', $now->month)
                ->whereYear('updated_at', $now->year)
                ->count(),
            'total_active' => Contract::whereIn('status', ['active', 'attivo', 'signed'])->count(),
        ];
    }

    /**
     * Send expiration email to internal user
     */
    private function sendExpirationEmail(User $user, Contract $contract, int $daysUntilExpiry): void
    {
        $subject = $daysUntilExpiry <= 7 
            ? "⚠️ URGENTE: Contratto {$contract->contract_number} in scadenza tra {$daysUntilExpiry} giorni"
            : "Contratto {$contract->contract_number} in scadenza tra {$daysUntilExpiry} giorni";

        Mail::raw(
            $this->buildExpirationEmailBody($contract, $daysUntilExpiry, true),
            function ($message) use ($user, $subject) {
                $message->to($user->email)
                        ->subject($subject);
            }
        );
    }

    /**
     * Send expiration email to client
     */
    private function sendClientExpirationEmail(Contract $contract, int $daysUntilExpiry): void
    {
        $clientEmail = $contract->client->email ?? $contract->client_email;
        
        if (!$clientEmail) {
            return;
        }

        $subject = "Avviso: Il tuo contratto {$contract->contract_number} è in scadenza";

        Mail::raw(
            $this->buildExpirationEmailBody($contract, $daysUntilExpiry, false),
            function ($message) use ($clientEmail, $subject) {
                $message->to($clientEmail)
                        ->subject($subject);
            }
        );
    }

    /**
     * Build expiration email body
     */
    private function buildExpirationEmailBody(Contract $contract, int $daysUntilExpiry, bool $isInternal): string
    {
        $clientName = $contract->client?->company_name ?? $contract->client_name ?? 'N/D';
        
        if ($isInternal) {
            return <<<EMAIL
Gentile collega,

Il seguente contratto è in scadenza:

📋 Contratto: {$contract->contract_number}
👤 Cliente: {$clientName}
📅 Data scadenza: {$contract->expires_at->format('d/m/Y')}
⏰ Giorni rimanenti: {$daysUntilExpiry}
🔄 Rinnovo automatico: {($contract->auto_renew ? 'Sì' : 'No')}

Valore contratto: €{$contract->value}

Per gestire il rinnovo o la chiusura del contratto, accedi al gestionale OPPLA.

---
Questo è un messaggio automatico del sistema OPPLA One Manager.
EMAIL;
        }

        return <<<EMAIL
Gentile Cliente,

Ti informiamo che il tuo contratto con OPPLA sta per scadere:

📋 Numero Contratto: {$contract->contract_number}
📅 Data Scadenza: {$contract->expires_at->format('d/m/Y')}
⏰ Giorni Rimanenti: {$daysUntilExpiry}

Per maggiori informazioni o per rinnovare il contratto, contatta il tuo referente commerciale.

Cordiali saluti,
Il Team OPPLA

---
Questo è un messaggio automatico. Per assistenza contattaci a info@oppla.it
EMAIL;
    }

    /**
     * Send renewal notification
     */
    private function sendRenewalNotification(Contract $oldContract, Contract $newContract): void
    {
        // Notify internal team
        $usersToNotify = collect();
        
        if ($oldContract->assignee) {
            $usersToNotify->push($oldContract->assignee);
        }
        if ($oldContract->creator) {
            $usersToNotify->push($oldContract->creator);
        }

        foreach ($usersToNotify as $user) {
            try {
                Mail::raw(
                    $this->buildRenewalEmailBody($oldContract, $newContract, true),
                    function ($message) use ($user, $newContract) {
                        $message->to($user->email)
                                ->subject("✅ Contratto rinnovato: {$newContract->contract_number}");
                    }
                );
            } catch (\Exception $e) {
                Log::warning("Failed to send renewal notification", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify client
        $clientEmail = $newContract->client?->email ?? $newContract->client_email;
        if ($clientEmail) {
            try {
                Mail::raw(
                    $this->buildRenewalEmailBody($oldContract, $newContract, false),
                    function ($message) use ($clientEmail, $newContract) {
                        $message->to($clientEmail)
                                ->subject("Il tuo contratto OPPLA è stato rinnovato");
                    }
                );
            } catch (\Exception $e) {
                Log::warning("Failed to send client renewal notification", [
                    'client_email' => $clientEmail,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Build renewal email body
     */
    private function buildRenewalEmailBody(Contract $oldContract, Contract $newContract, bool $isInternal): string
    {
        $clientName = $newContract->client?->company_name ?? $newContract->client_name ?? 'N/D';

        if ($isInternal) {
            return <<<EMAIL
Gentile collega,

Il seguente contratto è stato rinnovato automaticamente:

📋 Vecchio Contratto: {$oldContract->contract_number}
📋 Nuovo Contratto: {$newContract->contract_number}
👤 Cliente: {$clientName}
📅 Nuova scadenza: {$newContract->expires_at->format('d/m/Y')}
💰 Valore: €{$newContract->value}

Il nuovo contratto è già attivo.

---
Questo è un messaggio automatico del sistema OPPLA One Manager.
EMAIL;
        }

        return <<<EMAIL
Gentile Cliente,

Ti informiamo che il tuo contratto con OPPLA è stato rinnovato con successo.

📋 Nuovo Numero Contratto: {$newContract->contract_number}
📅 Nuova Data Scadenza: {$newContract->expires_at->format('d/m/Y')}
📅 Decorrenza: {$newContract->start_date->format('d/m/Y')}

Grazie per aver scelto OPPLA!

Per qualsiasi informazione, contatta il tuo referente commerciale.

Cordiali saluti,
Il Team OPPLA

---
Questo è un messaggio automatico. Per assistenza contattaci a info@oppla.it
EMAIL;
    }
}
