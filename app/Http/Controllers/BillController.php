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


public function getConfirmedBills()
{
    $userId = auth()->id();

    $bills = Bill::where('user_id', $userId)
                 ->where('status', 'pending') // أو الحالة التي تعني "مؤكدة" حسب تعريفك
                 ->with('items') // إذا عندك علاقة باسم billItems
                 ->get();

    return response()->json([
        'status' => true,
        'message' => 'Confirmed Bills have been successfully fetched.',
        'data' => $bills,
    ]);
}
public function getConfirmedBillDetails($billId)
{
    $userId = auth()->id();

    $bill = Bill::with('items')
                ->where('id', $billId)
                ->where('user_id', $userId)
                ->where('status', 'pending') // أو 'pending' حسب حالة التأكيد عندك
                ->first();

    if (!$bill) {
        return response()->json([
            'status' => false,
            'message' => 'الفاتورة غير موجودة أو غير مؤكدة.',
        ], 404);
    }

    $data = [
        'bill_id' => $bill->id,
        'bill_number' => $bill->bill_number,
        'status' => $bill->status,
        'total_amount' => number_format($bill->total_amount, 2),
        'created_at' => $bill->created_at->format('Y-m-d H:i:s'),
        'items' => $bill->items->map(function ($item) {
            return [
                'item_type' => $item->item_type,
                'item_id' => $item->item_id,
                'stock_quantity' => $item->stock_quantity,
                'unit_price' => number_format($item->unit_price, 2),
                'total_price' => number_format($item->total_price, 2),
            ];
        }),
    ];

    return response()->json([
        'status' => true,
        'message' => 'تم جلب تفاصيل الفاتورة المؤكدة بنجاح.',
        'data' => $data,
    ]);
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
