<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockReport;
use App\Models\Medicine;

class StockReportcontroller extends Controller
{
    public function store(Request $request)
    {
        $reportType = $request->input('report_type'); // daily, weekly, monthly

        // تحديد الفترة الزمنية حسب نوع التقرير
        $startDate = $this->getStartDate($reportType);
        $endDate = now();

        // عدد الأدوية التي ستنتهي خلال 30 يوم (أو خلال الفترة المحددة)
        $expiringSoonCount = Medicine::whereBetween('expiry_date', [$endDate, $endDate->copy()->addDays(30)])
                                    ->count();

        // عدد الأدوية التي الكمية فيها أقل من الحد الأدنى (مثلاً 10)
        $lowStockCount = Medicine::where('stock_quantity', '<', 10)->count();

        $report = StockReport::create([
            'admin_id' => auth()->id(),
            'report_type' => $reportType,
            'expiring_soon' => $expiringSoonCount,
            'low_stock' => $lowStockCount,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'تم إنشاء تقرير المخزون بنجاح.',
            'data' => $report,
        ]);
    }
 // 2. استعراض كل التقارير (Read All)
    public function index()
    {
        $reports = StockReport::where('admin_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تقارير المخزون بنجاح.',
            'data' => $reports,
        ]);
    }

    // 3. عرض تقرير مفرد (Read One)
    public function show($id)
    {
        $report = StockReport::where('admin_id', auth()->id())->find($id);

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

    // 4. تحديث ملاحظات التقرير (Update)
   public function update(Request $request, $id)
{
    $report = StockReport::where('admin_id', auth()->id())->find($id);

    if (!$report) {
        return response()->json([
            'status' => 404,
            'message' => 'التقرير غير موجود',
        ], 404);
    }

    $request->validate([
        'notes' => 'nullable|string',
    ]);

    // تعيين الملاحظات مباشرة (يسمح بأن تكون null أو سلسلة فارغة)
    $report->notes = $request->input('notes');

    $report->save();

    return response()->json([
        'status' => 200,
        'message' => 'تم تحديث التقرير بنجاح.',
        'data' => $report,
    ]);
}


    // 5. حذف تقرير (Delete)
    public function destroy($id)
    {
        $report = StockReport::where('admin_id', auth()->id())->find($id);

        if (!$report) {
            return response()->json([
                'status' => 404,
                'message' => 'التقرير غير موجود.',
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
