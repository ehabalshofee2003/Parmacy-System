<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class category extends Model
{
    use HasFactory;
       protected $fillable = [
        'name',
        'image',
    ];
  public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }
 public function supplies()
    {
        return $this->hasMany(supply::class);
    }
}
