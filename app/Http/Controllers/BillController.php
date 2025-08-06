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

public function sendToAdmin($id)
{
    $bill = Bill::findOrFail($id);

    // تحقق أن الفاتورة لم تُرسل مسبقًا
    if ($bill->status === 'sent') {
        return response()->json([
            'status' => false,
            'message' => 'The bill has already been sent.'
        ], 400);
    }

    $bill->status = 'sent';
     $bill->save();

    return response()->json([
        'status' => true,
        'message' => 'The bill has been successfully sent to management.',
        'data' => $bill
    ]);
}
public function sendConfirmedBillsToAdmin()
{
    // الحصول على الفواتير المؤكدة والتي لم تُرسل بعد
    $bills = Bill::where('status', 'confirmed')
                ->whereNull('sent_at')
                ->get();

    if ($bills->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'There are no confirmed bill to send.',
        ]);
    }

    foreach ($bills as $bill) {
        // هنا ممكن تضيف منطق الإرسال (بريد - إشعار - إلخ)
        // مثلاً: إرسال إشعار للإدمن، أو حفظ سجل للإرسال

        // تحديث الحالة ووقت الإرسال
        $bill->update([
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'All confirmed Bills have been sent to the Admin.',
        'count' => $bills->count(),
    ]);
}
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
            'message' => 'The bill does not exist or is unconfirmed.',
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
        'message' => 'The confirmed bill details have been successfully retrieved.',
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
            'message' => 'The bill has been successfully sent to the Admin.',
            'data' => new BillResource($bill),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Failed to send bill.',
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
                'message' => 'There are no bills to send.',
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
            'message' => ' All Bills have been sent successfully.',
            'data' => BillResource::collection($bills),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while sending Bills.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function getSentBills()
{
$sentBills = Bill::where('status', 'sent')->get();

    return response()->json([
        'status' => true,
        'message' => 'The Bills sent to the Admin have been successfully retrieved.',
        'data' => $sentBills,
    ]);
}

public function showSentBillDetails($id)
{
    // التحقق من أن الفاتورة موجودة ومُرسلة
    $bill = Bill::with(['items.medicine']) // جلب العناصر مع الدواء المرتبط
                ->where('id', $id)
                ->where('status', 'sent') // تأكد أنها مرسلة
                ->first();

    if (!$bill) {
        return response()->json([
            'status' => false,
            'message' => 'The bill sent could not be found.',
            'data' => null,
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'The details of the successfully sent bill have been retrieved.',
        'data' => [
            'bill_id' => $bill->id,
            'bill_number' => $bill->bill_number,
            'date' => $bill->created_at->toDateString(),
            'items' => $bill->items->map(function ($item) {
                return [
                    'medicine_name' => $item->medicine->name ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->quantity * $item->unit_price,
                ];
            }),
            'total' => $bill->items->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            }),
        ]
    ]);
}




}
