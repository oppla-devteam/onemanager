<?php

namespace App\Console\Commands;

use App\Services\EmailAutomationService;
use Illuminate\Console\Command;

class ProcessEmailSequencesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-sequences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due email sequence steps and send emails';

    public function __construct(
        private EmailAutomationService $emailService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📧 Processing email sequences...');

        $results = $this->emailService->processDueEmails();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $results['processed']],
                ['Sent', $results['sent']],
                ['Skipped', $results['skipped']],
                ['Failed', $results['failed']],
            ]
        );

        if ($results['sent'] > 0) {
            $this->info("✅ {$results['sent']} emails sent successfully");
        }

        if ($results['failed'] > 0) {
            $this->warn("⚠️ {$results['failed']} emails failed to send");
        }

        return self::SUCCESS;
    }
}
