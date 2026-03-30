<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PartnerReportService;
use App\Mail\PartnerMonthlyReport;
use Illuminate\Support\Facades\Mail;

class SendPartnerMonthlyReports extends Command
{
    protected $signature = 'partners:send-monthly-reports {year?} {month?}';
    protected $description = 'Send monthly performance reports to all active partners';

    public function handle(PartnerReportService $reportService)
    {
        $year = $this->argument('year') ?? now()->subMonth()->year;
        $month = $this->argument('month') ?? now()->subMonth()->month;

        $this->info("Generating monthly reports for {$month}/{$year}...");

        $reports = $reportService->generateAllPartnerReports($year, $month);
        $sent = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar(count($reports));
        $progressBar->start();

        foreach ($reports as $report) {
            try {
                if ($report['partner']['email']) {
                    Mail::to($report['partner']['email'])
                        ->send(new PartnerMonthlyReport($report));
                    $sent++;
                    $this->info("\n✓ Sent to {$report['partner']['name']} ({$report['partner']['email']})");
                } else {
                    $this->warn("\n✗ No email for {$report['partner']['name']}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\n✗ Failed for {$report['partner']['name']}: {$e->getMessage()}");
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("Reports sent successfully: {$sent}");
        if ($failed > 0) {
            $this->warn("Reports failed: {$failed}");
        }

        return 0;
    }
}
