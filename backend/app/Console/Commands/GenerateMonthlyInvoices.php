<?php

namespace App\Console\Commands;

use App\Services\AutomaticInvoicingService;
use App\Services\PartnerInvoicingService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly {month?} {year?} {--partners-only} {--deferred-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera fatture differite mensili e fatture Partner OPPLA';

    protected $invoicingService;
    protected $partnerService;

    public function __construct(
        AutomaticInvoicingService $invoicingService,
        PartnerInvoicingService $partnerService
    ) {
        parent::__construct();
        $this->invoicingService = $invoicingService;
        $this->partnerService = $partnerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->argument('month') ?: Carbon::now()->subMonth()->month;
        $year = $this->argument('year') ?: Carbon::now()->subMonth()->year;

        $this->info("🚀 Generazione fatture per {$month}/{$year}...");
        $this->newLine();

        $totalInvoices = 0;

        // 1. Fatture Partner OPPLA (se non è deferred-only)
        if (!$this->option('deferred-only')) {
            $this->info("📊 Generazione fatture Partner OPPLA...");
            
            try {
                $partnerInvoices = $this->partnerService->generateAllPartnerInvoices($month, $year);

                if (empty($partnerInvoices)) {
                    $this->warn('  Nessuna fattura Partner da generare.');
                } else {
                    $this->info("  ✓ Generate {count($partnerInvoices)} fatture Partner:");

                    foreach ($partnerInvoices as $invoice) {
                        $this->line("    - #{$invoice->numero_fattura} per {$invoice->client->ragione_sociale} - €{$invoice->totale}");
                    }

                    $totalInvoices += count($partnerInvoices);
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Errore: {$e->getMessage()}");
            }

            $this->newLine();
        }

        // 2. Fatture Differite (se non è partners-only)
        if (!$this->option('partners-only')) {
            $this->info("📦 Generazione fatture differite (ordini cash)...");

            try {
                $deferredInvoices = $this->invoicingService->generateMonthlyDeferredInvoices($month, $year);

                if (empty($deferredInvoices)) {
                    $this->warn('  Nessuna fattura differita da generare.');
                } else {
                    $this->info("  ✓ Generate {count($deferredInvoices)} fatture differite:");

                    foreach ($deferredInvoices as $invoice) {
                        $this->line("    - #{$invoice->numero_fattura} per {$invoice->client->ragione_sociale} - €{$invoice->totale}");
                    }

                    $totalInvoices += count($deferredInvoices);
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Errore: {$e->getMessage()}");
            }
        }

        $this->newLine();
        
        if ($totalInvoices > 0) {
            $this->info("Processo completato! Totale fatture generate: {$totalInvoices}");
            return 0;
        } else {
            $this->warn("⚠️  Nessuna fattura generata per il periodo {$month}/{$year}");
            return 0;
        }
    }
}
