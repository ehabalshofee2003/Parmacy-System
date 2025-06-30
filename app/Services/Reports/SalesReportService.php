<?php

namespace App\Services\Reports;

use App\Models\SalesReport;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * توليد تقرير مبيعات حسب نوع التقرير (يومي، أسبوعي، شهري)
     */
   public function generate(string $type = 'daily'): SalesReport
{
    $startDate = $this->getStartDate($type);
    $endDate = now();

    // أولاً: نتحقق إذا التقرير موجود أصلاً بنفس النوع ونفس الفترة الزمنية
    $existingReport = SalesReport::where('report_type', $type)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->first();

    if ($existingReport) {
        return $existingReport; // إذا وجدنا التقرير، نرجعه مباشرة
    }

    // إذا ما وجدنا تقرير سابق، ننشئ واحد جديد (نفس كود التوليد)
    $totalSales = DB::table('bills')
        ->where('status', 'sent')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('total_amount');

    $totalBills = DB::table('bills')
        ->where('status', 'sent')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->count();

    $topMedicine = DB::table('bill_items')
        ->join('medicines', 'bill_items.item_id', '=', 'medicines.id')
        ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
        ->select('medicines.name_en', DB::raw('SUM(bill_items.stock_quantity) as total_quantity'))
        ->where('bill_items.item_type', 'medicine')
        ->where('bills.status', 'sent')
        ->whereBetween('bills.created_at', [$startDate, $endDate])
        ->groupBy('medicines.name_en')
        ->orderByDesc('total_quantity')
        ->first();

    return SalesReport::create([
        'admin_id' => 1,
        'report_type' => $type,
        'total_sales' => $totalSales,
        'total_income' => $totalSales,
        'top_medicine' => $topMedicine ? $topMedicine->name_en : 'لا يوجد',
        'total_bills' => $totalBills,
        'notes' => null,
    ]);
}


    /**
     * حساب تاريخ البداية حسب نوع التقرير
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
