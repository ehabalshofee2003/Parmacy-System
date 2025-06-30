<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\SalesReport;
use Illuminate\Support\Facades\DB;

class SalesReportcontroller extends Controller
{
 // 1. إنشاء تقرير مبيعات جديد تلقائيًا (Create)

public function store(Request $request)
{
    $reportType = $request->input('report_type'); // daily, weekly, monthly

    $startDate = $this->getStartDate($reportType);
    $endDate = now();

    // إجمالي المبيعات من الفواتير المؤكدة ضمن الفترة
    $totalSales = DB::table('bills')
        ->where('status', 'confirmed')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('total_amount');

    // عدد الفواتير ضمن الفترة
    $totalBills = DB::table('bills')
        ->where('status', 'confirmed')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->count();

    // الدواء الأكثر مبيعًا (حسب الكمية) ضمن الفترة
    $topMedicine = DB::table('bill_items')
        ->join('medicines', 'bill_items.item_id', '=', 'medicines.id')
        ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
        ->select('medicines.name_en', DB::raw('SUM(bill_items.stock_quantity) as total_quantity'))
        ->where('bills.status', 'confirmed')
        ->whereBetween('bills.created_at', [$startDate, $endDate])
        ->where('bill_items.item_type', 'medicine')
        ->groupBy('medicines.name_en')
        ->orderByDesc('total_quantity')
        ->first();

    $report = SalesReport::create([
        'admin_id' => auth()->id(),
        'report_type' => $reportType,
        'total_sales' => $totalSales,
        'total_income' => $totalSales, // أو منطق منفصل للربح
        'top_medicine' => $topMedicine ? $topMedicine->name_en : 'لا يوجد',
        'total_bills' => $totalBills,
        'notes' => $request->input('notes'),
    ]);

    return response()->json([
        'status' => 200,
        'message' => 'تم إنشاء تقرير المبيعات بنجاح.',
        'data' => $report,
    ]);
}


    // 2. استعراض جميع تقارير المبيعات (Read All)
    public function index()
    {
        $reports = SalesReport::where('admin_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تقارير المبيعات بنجاح.',
            'data' => $reports,
        ]);
    }

    // 3. عرض تقرير مبيعات مفرد (Read One)
    public function show($id)
    {
        $report = SalesReport::where('admin_id', auth()->id())->find($id);

        if (!$report) {
            return response()->json([
                'status' => 404,
                'message' => 'التقرير غير موجود أو لا تملك صلاحية الوصول إليه.',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب التقرير بنجاح.',
            'data' => $report,
        ]);
    }

    // 4. تحديث ملاحظات تقرير المبيعات (Update)
    public function update(Request $request, $id)
    {
        $report = SalesReport::where('admin_id', auth()->id())->find($id);

        if (!$report) {
            return response()->json([
                'status' => 404,
                'message' => 'التقرير غير موجود أو لا تملك صلاحية التعديل عليه.',
            ], 404);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $report->notes = $request->input('notes', $report->notes);
        $report->save();

        return response()->json([
            'status' => 200,
            'message' => 'تم تحديث التقرير بنجاح.',
            'data' => $report,
        ]);
    }

    // 5. حذف تقرير مبيعات (Delete)
    public function destroy($id)
    {
        $report = SalesReport::where('admin_id', auth()->id())->find($id);

        if (!$report) {
            return response()->json([
                'status' => 404,
                'message' => 'التقرير غير موجود أو لا تملك صلاحية الحذف.',
            ], 404);
        }

        $report->delete();

        return response()->json([
            'status' => 200,
            'message' => 'تم حذف التقرير بنجاح.',
        ]);
    }

    // دالة مساعدة لحساب تاريخ بداية الفترة حسب نوع التقرير
    private function getStartDate($reportType)
    {
        switch ($reportType) {
            case 'weekly':
                return now()->subWeek();
            case 'monthly':
                return now()->subMonth();
            case 'daily':
            default:
                return now()->startOfDay();
        }
    }

}
