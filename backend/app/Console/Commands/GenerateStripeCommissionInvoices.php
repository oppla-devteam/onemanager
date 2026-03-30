<?php

namespace App\Console\Commands;

use App\Services\StripeCommissionInvoicingService;
use App\Services\StripeService;
use App\Services\FattureInCloudService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateStripeCommissionInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:generate-commission-invoices {month?} {year?} {--preview : Solo anteprima senza generare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera fatture differite per commissioni Stripe riscosse (application fees)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->argument('month') ?: Carbon::now()->subMonth()->month;
        $year = $this->argument('year') ?: Carbon::now()->subMonth()->year;
        $preview = $this->option('preview');

        $this->info("🎯 Generazione fatture commissioni Stripe - {$month}/{$year}");
        $this->newLine();

        $service = new StripeCommissionInvoicingService(
            new StripeService(),
            new FattureInCloudService()
        );

        try {
            if ($preview) {
                // Solo anteprima
                $this->info("📋 Modalità ANTEPRIMA - Nessuna fattura verrà creata");
                $this->newLine();

                $previews = $service->pregenerateCommissionInvoices($month, $year);

                if (empty($previews)) {
                    $this->warn('Nessuna commissione trovata per il periodo indicato.');
                    return 0;
                }

                $this->info("Trovati " . count($previews) . " partner con commissioni:");
                $this->newLine();

                $table = [];
                foreach ($previews as $preview) {
                    $status = $preview['invoice_ready'] ?? false ? '✓ Pronta' : '✗ Errore';
                    
                    if (isset($preview['error'])) {
                        $status = '✗ ' . $preview['error'];
                    }

                    $table[] = [
                        'Partner' => $preview['partner_name'],
                        'Email' => $preview['partner_email'],
                        'Cliente' => $preview['client_name'] ?? 'N/A',
                        'Commissioni' => '€ ' . number_format($preview['total_commissions'] ?? 0, 2, ',', '.'),
                        'Coupon' => '€ ' . number_format($preview['total_coupons'] ?? 0, 2, ',', '.'),
                        'Netto' => '€ ' . number_format($preview['net_amount'] ?? 0, 2, ',', '.'),
                        'Transazioni' => $preview['transaction_count'] ?? 0,
                        'Status' => $status,
                    ];
                }

                $this->table(
                    ['Partner', 'Email', 'Cliente', 'Commissioni', 'Coupon', 'Netto', 'Transazioni', 'Status'],
                    $table
                );

                $readyCount = count(array_filter($previews, fn($p) => $p['invoice_ready'] ?? false));
                $this->newLine();
                $this->info("✓ {$readyCount} fatture pronte per essere generate");
                $this->warn("✗ " . (count($previews) - $readyCount) . " fatture con errori (partner non associati)");

            } else {
                // Genera fatture
                $this->info("🚀 Generazione fatture in corso...");
                $invoices = $service->generateMonthlyCommissionInvoices($month, $year);

                if (empty($invoices)) {
                    $this->warn('Nessuna fattura generata.');
                    return 0;
                }

                $this->newLine();
                $this->info("✓ Generate {count($invoices)} fatture differite:");
                $this->newLine();

                foreach ($invoices as $invoice) {
                    $this->line("  - #{$invoice->numero_fattura} per {$invoice->client->ragione_sociale} - €{$invoice->totale}");
                }

                $totalAmount = collect($invoices)->sum('totale');
                $this->newLine();
                $this->info("💰 Totale fatturato: € " . number_format($totalAmount, 2, ',', '.'));
                $this->newLine();

                // Chiedi conferma per invio a FIC
                if ($this->confirm('Vuoi inviare le fatture a Fatture in Cloud?', false)) {
                    $this->info("📤 Invio fatture a Fatture in Cloud...");
                    
                    $results = $service->sendDeferredInvoicesToFIC($month, $year);
                    $successCount = count(array_filter($results, fn($r) => $r['success']));
                    
                    $this->newLine();
                    $this->info("✓ Inviate {$successCount} fatture su " . count($results));
                    
                    foreach ($results as $result) {
                        if ($result['success']) {
                            $this->line("  ✓ {$result['invoice_number']} - {$result['client_name']}");
                        } else {
                            $this->error("  ✗ {$result['invoice_number']} - {$result['client_name']}: {$result['error']}");
                        }
                    }
                }
            }

            $this->newLine();
            $this->info('Operazione completata con successo!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Errore: ' . $e->getMessage());
            return 1;
        }
    }
}
