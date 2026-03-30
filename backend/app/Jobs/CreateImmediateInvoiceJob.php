<?php

namespace App\Jobs;

use App\Services\AutomaticInvoicingService;
use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateImmediateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $delivery;

    /**
     * Create a new job instance.
     */
    public function __construct(Delivery $delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * Execute the job.
     */
    public function handle(AutomaticInvoicingService $invoicingService): void
    {
        try {
            // Verifica che sia pagamento online e non abbia già fattura
            if ($this->delivery->payment_method !== 'online' || $this->delivery->invoice_id) {
                Log::info('Delivery non richiede fattura immediata', [
                    'delivery_id' => $this->delivery->id,
                    'payment_method' => $this->delivery->payment_method,
                    'has_invoice' => (bool) $this->delivery->invoice_id,
                ]);
                return;
            }

            $invoice = $invoicingService->createImmediateInvoiceForDelivery($this->delivery);

            Log::info('Fattura immediata creata da job', [
                'delivery_id' => $this->delivery->id,
                'invoice_id' => $invoice->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Errore creazione fattura immediata in job', [
                'delivery_id' => $this->delivery->id,
                'error' => $e->getMessage(),
            ]);

            // Rilancia l'eccezione per retry automatico
            throw $e;
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;
}
