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

}
