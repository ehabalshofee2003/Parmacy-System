<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;
    protected $guarded = [];
protected $fillable = [
    'name_en',
    'name_ar',
    'barcode',
    'category_id',
    'image_url',
    'manufacturer',
    'pharmacy_price',
    'consumer_price',
    'discount',
    'stock_quantity',
    'expiry_date',
    'composition',
    'needs_prescription',
    'reorder_level',
    'admin_id',
];


 protected $casts = [
        'discount' => 'float',
        'pharmacy_price' => 'float',
        'consumer_price' => 'float',
        'stock_quantity' => 'integer',
        'expiry_date' => 'date',
        'needs_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];
 public function category()
    {
        return $this->belongsTo(Category::class);
    }

public function invoiceItems() {
    return $this->hasMany(Bill_item::class);
}

   public function cartItems()
    {
        return $this->morphMany(Cart_items::class, 'item');
    }

}
