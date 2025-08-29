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
        'customer_name',
        'user_cart_id'
     ];
public function bill()
{
    return $this->belongsTo(Bill::class);
}

public function items()
{
    return $this->hasMany(Cart_items::class);
}


 public function user()
 {
    return $this->belongsTo(User::class);
 }
protected static function boot()
{
    parent::boot();

  static::creating(function ($cart) {
    $userId = auth()->id();

    // الحصول على عداد الفواتير لهذا المستخدم أو إنشاؤه
    $counter = InvoiceCounter::firstOrCreate(
        ['user_id' => $userId],
        ['last_number' => 0]
    );

    // زيادة الرقم
    $nextNumber = $counter->last_number + 1;

    // حفظ الرقم الجديد
    $counter->update(['last_number' => $nextNumber]);

    // توليد رقم الفاتورة بصيغة 0001
    $cart->bill_number = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
});
    // عند حذف سلة، حذف جميع العناصر المرتبطة بها
    static::deleting(function ($cart) {
        $cart->items()->delete(); // حذف كل العناصر المرتبطة بالسلة
    });
}
 public function recalculateTotal()
    {
        $this->total_price = $this->items()->sum('total_price');
        $this->save();
 }

}
