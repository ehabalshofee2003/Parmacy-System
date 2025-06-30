<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReport extends Model
{
    use HasFactory;
     protected $fillable = [
        'admin_id',
        'report_type',
        'expiring_soon',
        'low_stock',
        'notes',
    ];

    // العلاقة مع جدول المستخدم (Admin)
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
