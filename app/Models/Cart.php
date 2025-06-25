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


    public function user()
    {
        return $this->belongsTo(User::class);
    }
protected static function boot()
{
    parent::boot(); // استدعاء دالة الأب boot لضمان عمل الـ Eloquent بشكل صحيح

    // عند إنشاء سلة جديدة (قبل الحفظ)، توليد رقم فاتورة تلقائي
    static::creating(function ($cart) {
        $lastId = Cart::max('id') ?? 0; // جلب أكبر id موجود أو 0 إذا لا يوجد
        $nextId = $lastId + 1;          // رقم جديد هو التالي بعد الأكبر
        $cart->bill_number = str_pad($nextId, 4, '0', STR_PAD_LEFT); // تنسيق الرقم بـ 4 خانات مع أصفار
    });

    // عند حذف سلة، حذف جميع العناصر المرتبطة بها
    static::deleting(function ($cart) {
        $cart->items()->delete(); // حذف كل العناصر المرتبطة بالسلة
    });
}


}
