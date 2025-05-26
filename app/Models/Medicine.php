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
        'pharmacy_price',
        'consumer_price',
        'barcode',
        'category_id',
        'form',
        'size',
        'composition',
        'description',
        'stock_quantity',
        'expiry_date',
        'manufacturer',
        'country_of_origin',
        'needs_prescription',
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

}
