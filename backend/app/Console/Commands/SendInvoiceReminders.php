<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Mail\InvoiceReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendInvoiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-reminders 
                            {--days=7 : Days after due date to send reminder}
                            {--test : Test mode - show invoices but don\'t send emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for overdue unpaid invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysOverdue = (int) $this->option('days');
        $testMode = $this->option('test');

        $this->info('Checking for overdue invoices...');

        // Find unpaid invoices that are overdue
        $overdueDate = Carbon::now()->subDays($daysOverdue);

        $invoices = Invoice::where('payment_status', 'unpaid')
            ->where('type', 'attiva')
            ->where('data_scadenza', '<=', $overdueDate)
            ->whereNull('cancelled_at')
            ->with(['client'])
            ->get();

        // Filter out invoices that already had a reminder sent recently (within last 7 days)
        $invoices = $invoices->filter(function ($invoice) {
            return !$invoice->last_reminder_sent_at || 
                   $invoice->last_reminder_sent_at->lt(Carbon::now()->subDays(7));
        });

        if ($invoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return 0;
        }

        $this->info("Found {$invoices->count()} overdue invoices");

        if ($testMode) {
            $this->warn('TEST MODE - No emails will be sent');
        }

        $sent = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($invoices->count());
        $progressBar->start();

        foreach ($invoices as $invoice) {
            try {
                $client = $invoice->client;

                // Check if client has email
                if (!$client->email && !$client->pec) {
                    $this->warn("\nCliente {$client->ragione_sociale} senza email - Skip fattura {$invoice->numero_fattura}");
                    $failed++;
                    $progressBar->advance();
                    continue;
                }

                $daysLate = Carbon::parse($invoice->data_scadenza)->diffInDays(Carbon::now());
                
                if ($testMode) {
                    $this->line("\nWould send reminder for invoice {$invoice->numero_fattura} to {$client->email} ({$daysLate} days late)");
                } else {
                    // Send email
                    $recipientEmail = $client->pec ?? $client->email;
                    
                    Mail::to($recipientEmail)->send(
                        new InvoiceReminderMail($invoice, $daysLate)
                    );

                    // Update last reminder sent timestamp
                    $invoice->update([
                        'last_reminder_sent_at' => Carbon::now(),
                        'reminder_count' => ($invoice->reminder_count ?? 0) + 1,
                    ]);

                    $this->info("\nReminder sent for invoice {$invoice->numero_fattura} ({$daysLate} days late)");
                }

                $sent++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $this->error("\nError sending reminder for invoice {$invoice->numero_fattura}: " . $e->getMessage());
                $failed++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Reminders processing completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Sent', $sent],
                ['Failed', $failed],
            ]
        );

        return 0;
    }
}
