<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockReport;
use App\Models\Medicine;
use Barryvdh\DomPDF\Facade\Pdf;



 class StockReportcontroller extends Controller
{
   public function store(Request $request)
{
    $report = app(\App\Services\Reports\StockReportService::class)
                 ->generate($request->input('report_type', 'daily'));

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

    public function downloadPDF($id)
    {
        $report = StockReport::find($id);

        if (!$report) {
            return response()->json([
                'status' => false,
                'message' => 'التقرير غير موجود',
            ], 404);
        }

        $pdf = PDF::loadView('reports.stock_report_pdf', ['report' => $report]);

        return $pdf->download('stock_report_' . $report->id . '.pdf');
    }

}
