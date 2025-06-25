<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
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
            'customer_name' => $this->customer_name,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'items' => CartItemResource::collection($this->cart->items ?? []),
        ];
    }
}
