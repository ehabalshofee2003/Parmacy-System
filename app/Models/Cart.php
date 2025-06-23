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


}
