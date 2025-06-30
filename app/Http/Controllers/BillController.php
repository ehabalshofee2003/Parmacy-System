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
        $user = auth()->user();

        // إذا كان Admin، يبحث فقط حسب ID
        $query = Bill::with('items')->where('id', $id);

        // إذا كان صيدلي، يقيّد حسب الـ user_id
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $bill = $query->first();

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


public function index()
{
    try {
        $user = auth()->user();

        // إذا كان المسؤول، يعرض كل الفواتير
        if ($user->role === 'admin') {
            $bills = Bill::with('items')
                        ->orderBy('created_at', 'desc')
                        ->get();
        } else {
            // المستخدم العادي يرى فقط فواتيره
            $bills = Bill::with('items')
                        ->where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->get();
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الفواتير بنجاح.',
            'data' => BillResource::collection($bills)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء جلب الفواتير.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function sendSingleBillToAdmin($id)
{
    try {
        $bill = Bill::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->firstOrFail();

        $bill->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'تم إرسال الفاتورة إلى الأدمن بنجاح.',
            'data' => new BillResource($bill),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'فشل في إرسال الفاتورة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function sendAllBillsToAdmin()
{
    try {
        $bills = Bill::where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->get();

        if ($bills->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'لا توجد فواتير لإرسالها.',
            ]);
        }

        foreach ($bills as $bill) {
            $bill->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'تم إرسال جميع الفواتير بنجاح.',
            'data' => BillResource::collection($bills),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'حدث خطأ أثناء إرسال الفواتير.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



}
