<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class supply extends Model
{
    use HasFactory;
     protected $fillable = [
    'title',
    'category_id',
    'pharmacy_price',
    'consumer_price',
    'discount',
    'stock_quantity',
    'image'
    ];
    public function category()
{
    return $this->belongsTo(Category::class);
}

}
