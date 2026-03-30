<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FattureInCloudConnection;
use App\Services\FattureInCloudService;

class ManageFicWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fic:webhooks 
                            {action : Action to perform (list, create, delete)}
                            {--subscription-id= : Subscription ID for delete action}
                            {--url= : Callback URL for create action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Fatture in Cloud webhook subscriptions';

    protected FattureInCloudService $ficService;

    public function __construct(FattureInCloudService $ficService)
    {
        parent::__construct();
        $this->ficService = $ficService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        // Get active FIC connection
        $ficConnection = FattureInCloudConnection::where('is_active', true)->first();

        if (!$ficConnection) {
            $this->error('No active Fatture in Cloud connection found');
            return 1;
        }

        switch ($action) {
            case 'list':
                return $this->listSubscriptions($ficConnection);

            case 'create':
                return $this->createSubscription($ficConnection);

            case 'delete':
                return $this->deleteSubscription($ficConnection);

            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: list, create, delete");
                return 1;
        }
    }

    /**
     * List all webhook subscriptions
     */
    private function listSubscriptions(FattureInCloudConnection $connection): int
    {
        $this->info('Fetching webhook subscriptions...');

        $subscriptions = $this->ficService->listWebhookSubscriptions($connection);

        if ($subscriptions === null) {
            $this->error('Failed to fetch subscriptions');
            return 1;
        }

        if (empty($subscriptions)) {
            $this->warn('No webhook subscriptions found');
            return 0;
        }

        $this->info('Found ' . count($subscriptions) . ' subscription(s)');
        $this->newLine();

        $rows = [];
        foreach ($subscriptions as $sub) {
            $rows[] = [
                $sub['id'] ?? 'N/A',
                $sub['sink'] ?? 'N/A',
                implode(', ', $sub['types'] ?? []),
                $sub['config']['include_document']['default'] ?? 'false',
            ];
        }

        $this->table(
            ['ID', 'Callback URL', 'Event Types', 'Include Document'],
            $rows
        );

        return 0;
    }

    /**
     * Create a new webhook subscription
     */
    private function createSubscription(FattureInCloudConnection $connection): int
    {
        $callbackUrl = $this->option('url');

        if (!$callbackUrl) {
            $callbackUrl = $this->ask('Enter webhook callback URL', config('app.url') . '/api/webhooks/fatture-in-cloud');
        }

        $this->info("Creating webhook subscription for: {$callbackUrl}");

        // Ask which event types to subscribe to
        $allEventTypes = [
            'it.fattureincloud.webhooks.issued_documents.create',
            'it.fattureincloud.webhooks.issued_documents.update',
            'it.fattureincloud.webhooks.issued_documents.delete',
            'it.fattureincloud.webhooks.clients.create',
            'it.fattureincloud.webhooks.clients.update',
            'it.fattureincloud.webhooks.clients.delete',
            'it.fattureincloud.webhooks.suppliers.create',
            'it.fattureincloud.webhooks.suppliers.update',
            'it.fattureincloud.webhooks.suppliers.delete',
        ];

        $this->warn('Available event types:');
        foreach ($allEventTypes as $index => $type) {
            $this->line(($index + 1) . ". {$type}");
        }

        if ($this->confirm('Subscribe to all event types?', true)) {
            $eventTypes = $allEventTypes;
        } else {
            // Let user select specific events
            $selectedIndices = $this->ask('Enter event numbers to subscribe (comma-separated)', '1,2,3');
            $indices = array_map('trim', explode(',', $selectedIndices));
            
            $eventTypes = [];
            foreach ($indices as $idx) {
                if (isset($allEventTypes[(int)$idx - 1])) {
                    $eventTypes[] = $allEventTypes[(int)$idx - 1];
                }
            }
        }

        if (empty($eventTypes)) {
            $this->error('No event types selected');
            return 1;
        }

        $this->info('Selected event types: ' . implode(', ', $eventTypes));

        // Create subscription
        $subscription = $this->ficService->createWebhookSubscription(
            $connection,
            $callbackUrl,
            $eventTypes
        );

        if (!$subscription) {
            $this->error('Failed to create webhook subscription');
            return 1;
        }

        $this->info('✓ Webhook subscription created successfully!');
        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Subscription ID', $subscription['id'] ?? 'N/A'],
                ['Callback URL', $subscription['sink'] ?? 'N/A'],
                ['Event Types', implode("\n", $subscription['types'] ?? [])],
            ]
        );

        $this->warn('⚠️  Subscriptions expire after 30 days. Remember to renew or recreate!');

        return 0;
    }

    /**
     * Delete a webhook subscription
     */
    private function deleteSubscription(FattureInCloudConnection $connection): int
    {
        $subscriptionId = $this->option('subscription-id');

        if (!$subscriptionId) {
            // List existing subscriptions first
            $subscriptions = $this->ficService->listWebhookSubscriptions($connection);

            if (empty($subscriptions)) {
                $this->warn('No webhook subscriptions found');
                return 0;
            }

            $this->info('Existing subscriptions:');
            foreach ($subscriptions as $index => $sub) {
                $this->line(($index + 1) . ". {$sub['id']} - {$sub['sink']}");
            }

            $selectedIndex = $this->ask('Enter subscription number to delete');
            $subscriptionId = $subscriptions[(int)$selectedIndex - 1]['id'] ?? null;

            if (!$subscriptionId) {
                $this->error('Invalid selection');
                return 1;
            }
        }

        if (!$this->confirm("Delete subscription {$subscriptionId}?", false)) {
            $this->info('Cancelled');
            return 0;
        }

        $success = $this->ficService->deleteWebhookSubscription($connection, $subscriptionId);

        if ($success) {
            $this->info("✓ Subscription {$subscriptionId} deleted successfully!");
            return 0;
        } else {
            $this->error("Failed to delete subscription {$subscriptionId}");
            return 1;
        }
    }
}
