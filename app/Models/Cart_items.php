<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart_items extends Model
{
    use HasFactory;
     protected $fillable = [
        'cart_id',
        'item_type',

        'item_id',
        'stock_quantity',
        'unit_price',
        'total_price'
     ];
public function cart()
{
    return $this->belongsTo(Cart::class);
}


}
