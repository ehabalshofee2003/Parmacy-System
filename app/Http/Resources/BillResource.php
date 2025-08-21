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
            'id'           => $this->id,
            'bill_number'  => $this->bill_number,
            'total_amount' => $this->total_amount,
            'status'       => $this->status,
            'items'        => BillItemResource::collection($this->items),
        ];
    }
}
