<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reference'      => $this->reference ?? null,
            'status'         => $this->status,
            'payment_status' => $this->payment_status ?? null,
            'total'          => (float) ($this->total ?? 0),
            'buyer_id'       => $this->buyer_id,
            'dealer_id'      => $this->dealer_id,
            'items'          => $this->whenLoaded('items'),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
