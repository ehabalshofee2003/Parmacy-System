<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart_items extends Model
{
    use HasFactory;

       protected $fillable = [
        'cart_id', 'item_id', 'item_type', 'stock_quantity',
        'unit_price', 'total_price'
    ];
  public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

public function medicine()
{
    return $this->belongsTo(Medicine::class, 'item_id');
}

public function supply()
{
    return $this->belongsTo(Supply::class, 'item_id');
}
    

}
