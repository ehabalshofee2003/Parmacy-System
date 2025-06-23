<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'stock_quantity'  => $this->stock_quantity,
            'reorder_level'   => $this->reorder_level,
            'category'        => $this->category->name ?? null, // assuming علاقة category موجودة
         ];
    }
}
