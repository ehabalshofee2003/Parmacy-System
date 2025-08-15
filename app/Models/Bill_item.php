<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill_item extends Model
{
    use HasFactory;
       protected $fillable = [
        'bill_id',
        'item_type',
        'item_id',
        'stock_quantity',
        'unit_price',
        'total_price'
    ];
    public function Bills() {
    return $this->belongsTo(Bill::class);
}

public function medicine()
{
    return $this->belongsTo(Medicine::class, 'item_id');
}


}
