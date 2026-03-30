<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeliveryZone;

class CleanupRestaurantZones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zones:cleanup-restaurants {--dry-run : Preview changes without deleting} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove delivery zones with restaurant names instead of geographic zones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Analyzing delivery zones for restaurant names...');
        $this->newLine();

        // Restaurant keywords for detection
        $restaurantKeywords = [
            'pizzeria', 'ristorante', 'trattoria', 'osteria', 'bar',
            'cafè', 'cafe', 'bistrot', 'bistro', 'pub', 'paninoteca',
            'hamburgeria', 'gelateria', 'pasticceria', 'braceria',
            'steakhouse', 'sushi', 'poke', 'kebab', 'street food',
            'da ', 'al ', 'la ', 'il ', 'lo ', "l'",
        ];

        // Get all zones
        $zones = DeliveryZone::all();
        $suspicious = [];

        foreach ($zones as $zone) {
            $nameLower = mb_strtolower($zone->name);
            $isSuspicious = false;
            $reason = '';

            // Check for restaurant keywords
            foreach ($restaurantKeywords as $keyword) {
                if (str_contains($nameLower, $keyword)) {
                    $isSuspicious = true;
                    $reason = "Contains keyword: '{$keyword}'";
                    break;
                }
            }

            // Additional patterns
            if (!$isSuspicious) {
                if (preg_match("/[ld]'[a-z]/i", $zone->name)) {
                    $isSuspicious = true;
                    $reason = "Contains apostrophe pattern (L'/D')";
                } elseif (str_contains($zone->name, '&')) {
                    $isSuspicious = true;
                    $reason = "Contains '&' symbol";
                } elseif (preg_match('/^\d+\s+[a-z]/i', $zone->name)) {
                    $isSuspicious = true;
                    $reason = "Starts with number + word";
                } elseif (str_word_count($zone->name) > 3) {
                    $hasGeoIndicator = preg_match('/(centro|nord|sud|est|ovest|zona|area)/i', $zone->name);
                    if (!$hasGeoIndicator) {
                        $isSuspicious = true;
                        $reason = "Long name (>3 words) without geographic indicators";
                    }
                }
            }

            if ($isSuspicious) {
                $suspicious[] = [
                    'zone' => $zone,
                    'reason' => $reason,
                ];
            }
        }

        if (empty($suspicious)) {
            $this->info('✅ No suspicious zones found!');
            return 0;
        }

        $suspiciousCount = count($suspicious);
        $this->warn("Found {$suspiciousCount} zones with restaurant names:");
        $this->newLine();

        // Display suspicious zones
        $table = [];
        foreach ($suspicious as $item) {
            $zone = $item['zone'];
            $table[] = [
                $zone->id,
                $zone->name,
                $zone->city,
                $zone->oppla_id ?? 'N/A',
                $item['reason'],
            ];
        }

        $this->table(
            ['ID', 'Name', 'City', 'OPPLA ID', 'Reason'],
            $table
        );

        if ($dryRun) {
            $this->info('🔸 Dry run mode - No zones were deleted');
            return 0;
        }

        // Confirm deletion
        if (!$force) {
            if (!$this->confirm("Delete these {$suspiciousCount} zones?", false)) {
                $this->info('Operation cancelled');
                return 0;
            }
        }

        // Delete zones
        $bar = $this->output->createProgressBar(count($suspicious));
        $bar->start();

        $deleted = 0;
        foreach ($suspicious as $item) {
            $item['zone']->delete();
            $deleted++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully deleted {$deleted} zones with restaurant names");

        return 0;
    }
}
