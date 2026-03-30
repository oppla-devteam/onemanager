<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationController extends Controller
{
    /**
     * Store a new push subscription
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $user = $request->user();

        // Store in database (you'll need to create this table)
        $user->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $request->input('contentEncoding', 'aesgcm'),
            ]
        );

        return response()->json(['message' => 'Subscription saved']);
    }

    /**
     * Remove a push subscription
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = $request->user();
        $user->pushSubscriptions()->where('endpoint', $validated['endpoint'])->delete();

        return response()->json(['message' => 'Subscription removed']);
    }

    /**
     * Send a push notification to user
     */
    public static function sendToUser($userId, string $title, string $body, array $data = [])
    {
        $user = \App\Models\User::find($userId);
        if (!$user) return;

        $subscriptions = $user->pushSubscriptions;
        if ($subscriptions->isEmpty()) return;

        $auth = [
            'VAPID' => [
                'subject' => config('services.vapid.subject', 'mailto:admin@oppla.it'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/icon-192.svg',
            'badge' => '/icon-192.svg',
            'data' => $data,
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding ?? 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        // Send all notifications
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if (!$report->isSuccess()) {
                Log::error('Push notification failed', [
                    'endpoint' => $endpoint,
                    'reason' => $report->getReason(),
                ]);

                // Remove invalid subscriptions
                if ($report->isSubscriptionExpired()) {
                    $user->pushSubscriptions()->where('endpoint', $endpoint)->delete();
                }
            }
        }
    }

    /**
     * Send notification to all users
     */
    public static function sendToAll(string $title, string $body, array $data = [])
    {
        $users = \App\Models\User::whereHas('pushSubscriptions')->get();

        foreach ($users as $user) {
            self::sendToUser($user->id, $title, $body, $data);
        }
    }

    /**
     * Notification triggers for specific events
     */
    public static function notifyInvoiceCreated($invoice)
    {
        self::sendToUser(
            $invoice->created_by ?? 1,
            'Fattura Creata',
            "Fattura {$invoice->invoice_number} creata per {$invoice->client->ragione_sociale}",
            [
                'type' => 'invoice_created',
                'invoice_id' => $invoice->id,
                'url' => '/invoices/' . $invoice->id,
            ]
        );
    }

    public static function notifyInvoicePaid($invoice)
    {
        self::sendToAll(
            'Pagamento Ricevuto',
            "Fattura {$invoice->invoice_number} pagata - €" . number_format($invoice->total, 2),
            [
                'type' => 'invoice_paid',
                'invoice_id' => $invoice->id,
                'url' => '/invoices/' . $invoice->id,
            ]
        );
    }

    public static function notifyInvoiceOverdue($invoice)
    {
        self::sendToAll(
            'Fattura Scaduta',
            "Fattura {$invoice->invoice_number} scaduta - Cliente: {$invoice->client->ragione_sociale}",
            [
                'type' => 'invoice_overdue',
                'invoice_id' => $invoice->id,
                'url' => '/invoices/' . $invoice->id,
            ]
        );
    }

    public static function notifyContractSigned($contract)
    {
        self::sendToAll(
            'Contratto Firmato',
            "Contratto firmato da {$contract->client->ragione_sociale}",
            [
                'type' => 'contract_signed',
                'contract_id' => $contract->id,
                'url' => '/contracts/' . $contract->id,
            ]
        );
    }

    public static function notifyContractExpiring($contract, $daysUntilExpiry)
    {
        self::sendToAll(
            'Contratto in Scadenza',
            "Contratto con {$contract->client->ragione_sociale} scade tra {$daysUntilExpiry} giorni",
            [
                'type' => 'contract_expiring',
                'contract_id' => $contract->id,
                'url' => '/contracts/' . $contract->id,
            ]
        );
    }

    public static function notifyTaskAssigned($task, $userId)
    {
        self::sendToUser(
            $userId,
            'Nuovo Task Assegnato',
            $task->title,
            [
                'type' => 'task_assigned',
                'task_id' => $task->id,
                'url' => '/tasks',
            ]
        );
    }

    public static function notifySyncCompleted($type, $count)
    {
        self::sendToAll(
            'Sincronizzazione Completata',
            "Sync {$type}: {$count} elementi aggiornati",
            [
                'type' => 'sync_completed',
                'sync_type' => $type,
            ]
        );
    }

    public static function notifySyncFailed($type, $error)
    {
        self::sendToAll(
            'Errore Sincronizzazione',
            "Sync {$type} fallito: {$error}",
            [
                'type' => 'sync_failed',
                'sync_type' => $type,
            ]
        );
    }
}
