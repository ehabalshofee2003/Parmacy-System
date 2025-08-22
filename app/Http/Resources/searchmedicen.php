<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class searchmedicen extends JsonResource
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
            'name_en'        => $this->name_en,
            'image_url' => $this->image_url, // ما تستخدم asset() هون
            'stock_quantity' => $this->stock_quantity,
            'consumer_price' => $this->consumer_price,
            'expiry_date'    => $this->expiry_date,
            'category_id' =>$this->category_id
        ];
    }
}
