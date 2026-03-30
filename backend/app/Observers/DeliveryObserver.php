<?php

namespace App\Observers;

use App\Models\Delivery;
use App\Jobs\CreateImmediateInvoiceJob;
use Illuminate\Support\Facades\Log;

class DeliveryObserver
{
    /**
     * Handle the Delivery "created" event.
     */
    public function created(Delivery $delivery): void
    {
        // Se il pagamento è online, crea automaticamente la fattura immediata
        if ($delivery->payment_method === 'online' && !$delivery->invoice_id) {
            Log::info('Delivery con pagamento online creata, schedulando fattura immediata', [
                'delivery_id' => $delivery->id,
            ]);

            // Dispatch job per creare fattura
            CreateImmediateInvoiceJob::dispatch($delivery)
                ->delay(now()->addSeconds(30)); // Delay di 30 secondi per assicurare che tutto sia salvato
        }
    }

    /**
     * Handle the Delivery "updated" event.
     */
    public function updated(Delivery $delivery): void
    {
        // Se il metodo di pagamento cambia a online e non ha fattura, creala
        if ($delivery->isDirty('payment_method') && 
            $delivery->payment_method === 'online' && 
            !$delivery->invoice_id) {
            
            Log::info('Metodo pagamento cambiato a online, schedulando fattura immediata', [
                'delivery_id' => $delivery->id,
            ]);

            CreateImmediateInvoiceJob::dispatch($delivery)
                ->delay(now()->addSeconds(30));
        }
    }

    /**
     * Handle the Delivery "deleted" event.
     */
    public function deleted(Delivery $delivery): void
    {
        // TODO: Gestire eliminazione delivery con fattura
    }

    /**
     * Handle the Delivery "restored" event.
     */
    public function restored(Delivery $delivery): void
    {
        //
    }

    /**
     * Handle the Delivery "force deleted" event.
     */
    public function forceDeleted(Delivery $delivery): void
    {
        //
    }
}
