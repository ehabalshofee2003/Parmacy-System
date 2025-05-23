<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id'                  => $this->id,
            'name_en'             => $this->name_en,
            'name_ar'             => $this->name_ar,
            'manufacturer'        => $this->manufacturer,
            'country_of_origin'   => $this->country_of_origin,
            'expiry_date'         => $this->expiry_date,
            'pharmacy_price'      => $this->pharmacy_price,
            'consumer_price'      => $this->consumer_price,
            'discount'            => $this->discount,
            'barcode'             => $this->barcode,
            'form'                => $this->form,
            'size'                => $this->size,
            'composition'         => $this->composition,
            'description'         => $this->description,
            'quantity'            => $this->quantity,
            'needs_prescription'  => (bool) $this->needs_prescription,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }

}
