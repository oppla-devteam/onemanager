<?php

namespace App\Jobs;

use App\Services\InvoicingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessMonthlyInvoicing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minuti
    public $tries = 3;

    protected int $year;
    protected int $month;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $year = null, ?int $month = null)
    {
        // Di default processa il mese precedente
        $date = Carbon::now()->subMonth();
        $this->year = $year ?? $date->year;
        $this->month = $month ?? $date->month;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoicingService $invoicingService): void
    {
        Log::info("Inizio processamento fatturazione mensile: {$this->year}-{$this->month}");

        try {
            $results = $invoicingService->generateMonthlyInvoices($this->year, $this->month);

            $successful = collect($results)->where('status', 'success')->count();
            $failed = collect($results)->where('status', 'error')->count();

            Log::info("Fatturazione mensile completata", [
                'year' => $this->year,
                'month' => $this->month,
                'successful' => $successful,
                'failed' => $failed,
                'details' => $results,
            ]);

            // Invia notifica al CEO se ci sono errori
            if ($failed > 0) {
                // TODO: Invia notifica PWA o email
            }

        } catch (\Exception $e) {
            Log::error("Errore fatturazione mensile: {$e->getMessage()}", [
                'exception' => $e,
                'year' => $this->year,
                'month' => $this->month,
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job fatturazione mensile fallito dopo {$this->tries} tentativi", [
            'exception' => $exception->getMessage(),
            'year' => $this->year,
            'month' => $this->month,
        ]);

        // TODO: Notifica urgente al CEO
    }
}
