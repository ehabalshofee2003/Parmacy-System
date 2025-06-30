<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
      public function toArray(Request $request): array
    {
        return [
            'name_en'         => $this->title,
            'stock_quantity'        => $this->stock_quantity,
            'consumer_price'  => $this->consumer_price,
            'barcode'         => $this->barcode,
            'expiry_date'     => $this->expiry_date,
        ];
    }
}
