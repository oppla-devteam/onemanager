<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => new ClientResource($this->whenLoaded('client')),
            'numero_fattura' => $this->numero_fattura,
            'type' => $this->type,
            'data_emissione' => $this->data_emissione,
            'data_scadenza' => $this->data_scadenza,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at,
            'amount' => $this->amount,
            'vat_amount' => $this->vat_amount,
            'total_amount' => $this->total_amount,
            'sdi_sent_at' => $this->sdi_sent_at,
            'sdi_file_path' => $this->sdi_file_path,
            'stripe_transaction_id' => $this->stripe_transaction_id,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
