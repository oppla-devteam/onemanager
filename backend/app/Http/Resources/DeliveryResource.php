<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
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
            'delivery_code' => $this->delivery_code,
            'status' => $this->status,
            'rider_name' => $this->rider_name,
            'pickup_address' => $this->pickup_address,
            'delivery_address' => $this->delivery_address,
            'scheduled_at' => $this->scheduled_at,
            'picked_up_at' => $this->picked_up_at,
            'delivered_at' => $this->delivered_at,
            'distance_km' => $this->distance_km,
            'fee_base' => $this->fee_base,
            'fee_km' => $this->fee_km,
            'fee_total' => $this->fee_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
