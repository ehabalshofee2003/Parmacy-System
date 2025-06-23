<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
     protected $fillable = [
        'user_id',
        'bill_id',
        'status',
        'customer_name'
     ];
public function bill()
{
    return $this->belongsTo(Bill::class);
}

public function items()
{
    return $this->hasMany(Cart_items::class);
}
protected static function boot()
{
    parent::boot();

    static::creating(function ($cart) {
        // توليد رقم الفاتورة
        $lastId = Cart::max('id') ?? 0;
        $nextId = $lastId + 1;
        $cart->bill_number = 'BILL-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    });
}


}
