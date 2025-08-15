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
     public function toArray($request)
    {
        return [
            'bill_id' => $this->id,
            'bill_number' => $this->bill_number,
            'status' => $this->status,
            'total_amount' => number_format($this->total_amount, 2),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'items' => BillItemResource::collection($this->items), // استخدام Resource للعنصر
        ];
    }
}
