<?php

namespace App\Http\Resources;

use App\Models\Medicine;
use App\Models\supply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /*
   public function toArray(Request $request): array
{
    $itemName = null;

    if ($this->item_type === 'medicine') {
        $item = Medicine::find($this->item_id);
    } else {
        $item = Supply::find($this->item_id);
    }

    $itemName = $item?->title;

    return [
        'item_type'     => $this->item_type,
        'item_id'       => $this->item_id,
        'item_name'     => $itemName,
        'quantity'      => $this->stock_quantity,
        'unit_price'    => $this->unit_price,
        'total_price'   => $this->total_price,
    ];
}
    */
    public function toArray($request)
{
    return [
'item_type' => $this->item_type,        'item_id'      => $this->item_id,
'item_name' => $this->item_type === 'medicine'
    ? $this->medicine->name_en
    : ($this->supply->title ?? 'غير معروف'),
        'stock_quantity'     => $this->stock_quantity,
        'image_url' => $this->medicine->image_url,
        'unit_price'   => number_format($this->unit_price, 2),
        'total_price'  => number_format($this->total_price, 2),
    ];
}


}
