<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'guid' => $this->guid,
            'type' => $this->type,
            'ragione_sociale' => $this->ragione_sociale,
            'piva' => $this->piva,
            'codice_fiscale' => $this->codice_fiscale,
            'email' => $this->email,
            'phone' => $this->phone,
            'pec' => $this->pec,
            'sdi_code' => $this->sdi_code,
            'indirizzo' => $this->indirizzo,
            'citta' => $this->citta,
            'provincia' => $this->provincia,
            'cap' => $this->cap,
            'nazione' => $this->nazione,
            'stripe_customer_id' => $this->stripe_customer_id,
            'has_domain' => $this->has_domain,
            'has_pos' => $this->has_pos,
            'has_delivery' => $this->has_delivery,
            'is_partner_logistico' => $this->is_partner_logistico,
            'fee_mensile' => $this->fee_mensile,
            'fee_ordine' => $this->fee_ordine,
            'fee_consegna_base' => $this->fee_consegna_base,
            'fee_consegna_km' => $this->fee_consegna_km,
            'abbonamento_mensile' => $this->abbonamento_mensile,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
