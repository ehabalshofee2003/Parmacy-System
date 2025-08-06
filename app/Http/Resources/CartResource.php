<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
     public function toArray(Request $request): array
    {
        return [
            'cart_id' => $this->id,
            'customer_name' => $this->customer_name,
            'status' => $this->status,
            
            'items' => CartItemResource::collection($this->items),
            'total' => $this->items->sum('total_price'),
            'bill_number' =>$this->bill_number,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
