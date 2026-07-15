<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'description'      => $this->description,
            'category'         => $this->category,
            'price'            => (float) $this->price,
            'quantity_in_stock'=> (int) ($this->quantity_in_stock ?? 0),
            'unit'             => $this->unit ?? null,
            'status'           => $this->status,
            'is_approved'      => (bool) $this->is_approved,
            'image_url'        => $this->image_path
                ? url('storage/' . $this->image_path)
                : null,
            'dealer_id'        => $this->dealer_id,
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
