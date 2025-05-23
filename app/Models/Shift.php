<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{

    use HasFactory;
    protected $fillable = [
        'user_id', 'start_time', 'end_time', 'total_sales', 'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function user() {
    return $this->belongsTo(User::class);
}

public function Bills() {
    return $this->hasMany(Bill::class);
}

}
