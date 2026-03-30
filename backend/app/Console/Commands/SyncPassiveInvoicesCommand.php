<?php

namespace App\Console\Commands;

use App\Services\FattureInCloudService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncPassiveInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fic:sync-passive-invoices 
                            {--year= : Anno da sincronizzare (default: anno corrente)}
                            {--all : Sincronizza anche anno precedente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizza fatture passive (ricevute/acquisti) da Fatture in Cloud';

    public function __construct(
        private FattureInCloudService $ficService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = $this->option('year') ?? Carbon::now()->year;
        $syncAll = $this->option('all');

        $this->info("🔄 Sincronizzazione fatture passive da Fatture in Cloud...");

        $years = [$year];
        if ($syncAll) {
            $years[] = $year - 1;
        }

        $totalSynced = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSuppliersCreated = 0;
        $allErrors = [];

        foreach ($years as $syncYear) {
            $this->info("📅 Anno {$syncYear}...");

            $result = $this->ficService->syncPassiveInvoices($syncYear);

            $totalSynced += $result['synced'];
            $totalCreated += $result['created'];
            $totalUpdated += $result['updated'];
            $totalSuppliersCreated += $result['suppliers_created'];
            
            if (!empty($result['errors'])) {
                $allErrors = array_merge($allErrors, $result['errors']);
            }

            $this->line("  ✅ Sincronizzate: {$result['synced']}");
            $this->line("  ➕ Create: {$result['created']}");
            $this->line("  🔄 Aggiornate: {$result['updated']}");
            $this->line("  🏢 Nuovi fornitori: {$result['suppliers_created']}");
        }

        $this->newLine();
        $this->info("📊 Riepilogo totale:");
        $this->table(
            ['Metrica', 'Valore'],
            [
                ['Fatture sincronizzate', $totalSynced],
                ['Fatture create', $totalCreated],
                ['Fatture aggiornate', $totalUpdated],
                ['Nuovi fornitori', $totalSuppliersCreated],
            ]
        );

        if (!empty($allErrors)) {
            $this->newLine();
            $this->warn("⚠️ Errori riscontrati: " . count($allErrors));
            foreach (array_slice($allErrors, 0, 10) as $error) {
                $this->error("  - {$error}");
            }
            if (count($allErrors) > 10) {
                $this->warn("  ... e altri " . (count($allErrors) - 10) . " errori");
            }
        }

        $this->newLine();
        $this->info("✅ Sincronizzazione completata!");

        return empty($allErrors) ? self::SUCCESS : self::FAILURE;
    }
}
