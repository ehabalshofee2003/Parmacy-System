<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Bill_item;
use App\Models\Medicine;
use App\Http\Resources\BillResource;

class BillController extends Controller
{

public function show($id)
{
    try {
        if (auth()->user()->role !== 'pharmacist') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // جلب الفاتورة مع العناصر المرتبطة بها (عبر العلاقة)
        $bill = Bill::with('items')
                    ->where('user_id', auth()->id())
                    ->where('id', $id)
                    ->first();

        if (!$bill) {
            return response()->json([
                'status' => 404,
                'message' => 'الفاتورة غير موجودة أو لا تملك صلاحية الوصول إليها.'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل الفاتورة بنجاح.',
            'data' => new BillResource($bill)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب تفاصيل الفاتورة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


 
}
