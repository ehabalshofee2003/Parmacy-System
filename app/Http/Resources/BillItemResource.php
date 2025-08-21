<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
  public function toArray($request)
    {
        return [
            'item_type'      => $this->item_type,
            'item_id'        => $this->item_id,
            'item_name'      => $this->medicine?->name_en ?? 'Unknown',
            'stock_quantity' => $this->stock_quantity,
            'image_url'      => $this->medicine?->image_url,
            'unit_price'     => $this->unit_price,
            'total_price'    => $this->total_price,
        ];
    }
}
