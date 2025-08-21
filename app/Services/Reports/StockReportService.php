<?php

namespace App\Services\Reports;

use App\Models\StockReport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockReportService
{
    /**
     * توليد تقرير مخزون حسب النوع (يومي / أسبوعي / شهري)
     */
    public function generate(string $type = 'daily'): StockReport
    {
        $startDate = $this->getStartDate($type);
        $endDate = now();

        // // تحقق إذا التقرير موجود لنفس النوع والفترة
        // $existingReport = StockReport::where('report_type', $type)
        //     ->whereBetween('created_at', [$startDate, $endDate])
        //     ->first();

        // if ($existingReport) {
        //     return $existingReport; // إرجاع التقرير الموجود بدون إنشاء جديد
        // }

       $expiringSoon = DB::table('medicines')
    ->whereBetween('expiry_date', [now(), now()->addMonth()])
    ->count();


        // حساب عدد الأدوية منخفضة الكمية (مثلاً أقل من 10)
        $lowStock = DB::table('medicines')
            ->where('stock_quantity', '<', 10)
            ->count();

        // إنشاء تقرير جديد
        return StockReport::create([
            'admin_id' => 1, // أو auth()->id() لو مرتبط بالمستخدم الحالي
            'report_type' => $type,
            'expiring_soon' => $expiringSoon,
            'low_stock' => $lowStock,
            'notes' => null,
        ]);
    }



    /**
     * تحديد تاريخ البداية حسب نوع التقرير
     */
    private function getStartDate(string $type)
    {
        return match ($type) {
            'daily' => now()->startOfDay(),
            'weekly' => now()->subWeek()->startOfDay(),
            'monthly' => now()->subMonth()->startOfDay(),
            default => now()->startOfDay(),
        };
    }
}
