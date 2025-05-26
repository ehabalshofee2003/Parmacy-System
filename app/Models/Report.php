<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
       protected $fillable = [
        'admin_id',
        'report_type',
        'total_sales',
        'total_income',
        'Top_medicine',
        'expiring_soon',
        'low_stock',
        'notes'
    ];
    public function user() {
    return $this->belongsTo(User::class); // الأدمن الذي أنشأ التقرير
}

}
