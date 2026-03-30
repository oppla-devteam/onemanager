<?php

namespace App\Console\Commands;

use App\Models\Rider;
use App\Models\RiderStatistics;
use App\Services\TookanService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncTookanRiders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tookan:sync-riders
                            {--source=cron : Data source identifier (cron, manual, fallback)}
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     */
    protected $description = 'Sync rider data from Tookan API to local database';

    private TookanService $tookanService;

    /**
     * Execute the console command.
     */
    public function handle(TookanService $tookanService): int
    {
        $this->tookanService = $tookanService;
        $source = $this->option('source');
        $force = $this->option('force');

        $startTime = microtime(true);
        $this->info("🚀 Starting Tookan rider sync (source: {$source})...");

        try {
            // Check if we need to sync (skip if recently synced unless forced)
            if (!$force) {
                $lastSync = Rider::max('last_synced_at');
                if ($lastSync && Carbon::parse($lastSync)->gt(now()->subMinutes(4))) {
                    $this->info('⏭️  Skipping sync - last sync was less than 4 minutes ago');
                    return Command::SUCCESS;
                }
            }

            // Fetch riders from Tookan API
            $this->info('📡 Fetching riders from Tookan API...');
            $result = $this->tookanService->getAllAgents();

            if (!$result['success']) {
                $error = $result['error'] ?? 'Unknown error';
                $this->error("❌ Failed to fetch riders from Tookan: {$error}");
                Log::error('Tookan rider sync failed', [
                    'error' => $error,
                    'source' => $source,
                ]);
                return Command::FAILURE;
            }

            $tookanRiders = $result['data'] ?? [];
            $this->info("✅ Fetched " . count($tookanRiders) . " riders from Tookan");

            // Sync riders to database
            $stats = $this->syncRiders($tookanRiders);

            // Create statistics snapshot
            $this->createStatisticsSnapshot($source, $stats);

            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log results
            $this->info('');
            $this->info('📊 Sync Statistics:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Riders', $stats['total']],
                    ['Available', $stats['available']],
                    ['Busy', $stats['busy']],
                    ['Offline', $stats['offline']],
                    ['New Riders', $stats['created']],
                    ['Updated Riders', $stats['updated']],
                    ['Unchanged Riders', $stats['unchanged']],
                ]
            );
            $this->info("⏱️  Execution time: {$executionTime}ms");
            $this->info('✅ Sync completed successfully!');

            Log::info('Tookan rider sync completed', [
                'source' => $source,
                'stats' => $stats,
                'execution_time_ms' => $executionTime,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Sync failed with exception: " . $e->getMessage());
            Log::error('Tookan rider sync exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'source' => $source,
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Sync riders from Tookan to local database
     */
    private function syncRiders(array $tookanRiders): array
    {
        $stats = [
            'total' => 0,
            'available' => 0,
            'busy' => 0,
            'offline' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        $fleetIds = [];
        $now = now();

        DB::transaction(function () use ($tookanRiders, &$stats, &$fleetIds, $now) {
            foreach ($tookanRiders as $tookanRider) {
                $fleetId = $tookanRider['fleet_id'] ?? null;

                if (!$fleetId) {
                    Log::warning('Skipping rider without fleet_id', ['data' => $tookanRider]);
                    continue;
                }

                $fleetIds[] = $fleetId;

                // Map Tookan status to our status
                $statusCode = $tookanRider['status'] ?? 1;
                $status = match((int)$statusCode) {
                    0 => 'available',
                    2 => 'busy',
                    default => 'offline',
                };

                // Update stats
                $stats['total']++;
                $stats[$status]++;

                // Prepare rider data
                $riderData = [
                    'fleet_id' => $fleetId,
                    'username' => $tookanRider['username'] ?? null,
                    'first_name' => $tookanRider['first_name'] ?? null,
                    'last_name' => $tookanRider['last_name'] ?? null,
                    'email' => $tookanRider['email'] ?? null,
                    'phone' => $tookanRider['phone'] ?? null,
                    'status' => $status,
                    'status_code' => $statusCode,
                    'is_blocked' => (bool)($tookanRider['is_blocked'] ?? false),
                    'transport_type' => $this->mapTransportType($tookanRider['transport_type'] ?? 0),
                    'transport_type_code' => $tookanRider['transport_type'] ?? 0,
                    'latitude' => $tookanRider['latitude'] ?? null,
                    'longitude' => $tookanRider['longitude'] ?? null,
                    'team_id' => $tookanRider['team_id'] ?? null,
                    'team_name' => $tookanRider['team_name'] ?? null,
                    'tags' => $tookanRider['tags'] ?? null,
                    'profile_image' => $tookanRider['profile_image'] ?? null,
                    'fleet_last_updated_at' => isset($tookanRider['fleet_last_updated_at'])
                        ? Carbon::parse($tookanRider['fleet_last_updated_at'])
                        : null,
                    'last_synced_at' => $now,
                ];

                // Find or create rider
                $rider = Rider::where('fleet_id', $fleetId)->first();

                if ($rider) {
                    // Check if data actually changed
                    $changed = false;
                    foreach ($riderData as $key => $value) {
                        if ($key !== 'last_synced_at' && $rider->{$key} != $value) {
                            $changed = true;
                            break;
                        }
                    }

                    if ($changed) {
                        $rider->update($riderData);
                        $stats['updated']++;
                    } else {
                        // Just update the sync timestamp
                        $rider->update(['last_synced_at' => $now]);
                        $stats['unchanged']++;
                    }
                } else {
                    Rider::create($riderData);
                    $stats['created']++;
                }
            }

            // Mark riders not in Tookan response as offline (they may have been deleted)
            if (count($fleetIds) > 0) {
                $missingRiders = Rider::whereNotIn('fleet_id', $fleetIds)
                    ->where('status', '!=', 'offline')
                    ->get();

                foreach ($missingRiders as $rider) {
                    $rider->update([
                        'status' => 'offline',
                        'status_code' => 1,
                        'last_synced_at' => $now,
                    ]);
                    $this->warn("⚠️  Marked rider {$rider->name} ({$rider->fleet_id}) as offline (not found in Tookan)");
                }
            }
        });

        return $stats;
    }

    /**
     * Create a statistics snapshot
     */
    private function createStatisticsSnapshot(string $source, array $stats): void
    {
        RiderStatistics::create([
            'snapshot_time' => now(),
            'data_source' => $source,
            'total_riders' => $stats['total'],
            'available_riders' => $stats['available'],
            'busy_riders' => $stats['busy'],
            'offline_riders' => $stats['offline'],
        ]);

        $this->info('📸 Created statistics snapshot');
    }

    /**
     * Map Tookan transport type code to string
     */
    private function mapTransportType(int $code): string
    {
        return match($code) {
            0 => 'motorcycle',
            1 => 'bicycle',
            2 => 'car',
            3 => 'foot',
            4 => 'truck',
            default => 'motorcycle',
        };
    }
}
