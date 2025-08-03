<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\SalesReport;
use Illuminate\Support\Facades\DB;
use App\Services\Reports\SalesReportService;
use Barryvdh\DomPDF\Facade\Pdf;




class SalesReportcontroller extends Controller
{
 // 1. إنشاء تقرير مبيعات جديد تلقائيًا (Create)
public function store(Request $request)
{
    $report = app(SalesReportService::class)->generate($request->input('report_type', 'daily'));

    return response()->json([
        'status' => 200,
        'message' => 'تم إنشاء تقرير المبيعات بنجاح.',
        'data' => $report
    ]);
}
    // 2. استعراض جميع تقارير المبيعات (Read All)
public function index()
{
    $reports = SalesReport::orderBy('created_at', 'desc')->get();

    return response()->json([
        'status' => true,
        'message' => $reports->isEmpty()
            ? 'لا توجد تقارير مبيعات حالياً'
            : 'قائمة تقارير المبيعات',
        'data' => $reports->map(fn($r) => [
            'id' => $r->id,
            'report_type' => $r->report_type,
            'total_sales' => $r->total_sales,
            'total_bills' => $r->total_bills,
            'top_medicine' => $r->top_medicine,
            'notes' => $r->notes,
            'created_at' => $r->created_at->format('Y-m-d'),
            'download_url' => route('reports.sales.download', $r->id)
        ])
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
public function downloadPDF($id)
{
    $report = SalesReport::findOrFail($id);
    $pdf = Pdf::loadView('reports.sales_report_pdf', compact('report'));
    return $pdf->download("sales_report_{$report->id}.pdf");
}
}
/*
البحث عن موضوع تصدير التقاير كملفات pdf
لازم يتم تخزين التقارير كملفات بي دي اف في ملفات مشروع
ويمكن استعراض التقرير في لمتصفحات 
*/
