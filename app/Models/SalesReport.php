<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    use HasFactory;
      protected $fillable = [
        'admin_id',
        'report_type',
        'total_sales',
        'total_income',
        'top_medicine',
        'total_bills',
        'notes',
    ];

    // العلاقة مع جدول المستخدم (Admin)
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
