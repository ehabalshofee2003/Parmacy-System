<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill_item extends Model
{
    use HasFactory;
       protected $fillable = [
        'name',
        'email',
        'password',
    ];
    public function Bills() {
    return $this->belongsTo(Bill::class);
}

public function Medicine() {
    return $this->belongsTo(Medicine::class);
}

}
