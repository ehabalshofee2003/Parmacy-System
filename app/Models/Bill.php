<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;
       protected $fillable = [
        'bill_number',
        'user_id',
        'total_amount',
        'status'
     ];
    public function user() {
    return $this->belongsTo(User::class);
}

public function shift() {
    return $this->belongsTo(Shift::class);
}

public function items() {
    return $this->hasMany(Bill_item::class);
}

public function cart()
{
    return $this->hasOne(Cart::class);
}

protected static function boot()
{
    parent::boot();

    static::creating(function ($bill) {
         $count = Bill::whereDate('created_at', today())->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        $bill->bill_number =  "{$sequence}";
    });
}


}
