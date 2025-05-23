<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Bill_item;
use App\Models\Medicine;

class BillController extends Controller
{
    public function index()
{
    $bills = Bill::with('user')->latest()->paginate(10);

    return response()->json($bills);
}
public function show($id)
{
    $bill = Bill::with(['user', 'details.medicine'])->findOrFail($id);

    return response()->json($bill);
}

     public function store(Request $request)
{
    $request->validate([
        'items' => 'required|array',
        'items.*.medicine_id' => 'required|exists:medicines,id',
        'items.*.quantity' => 'required|integer|min:1',
    ]);

    DB::beginTransaction();

    try {
        $totalAmount = 0;
        $totalDiscount = 0;

        foreach ($request->items as $item) {
            $medicine = Medicine::findOrFail($item['medicine_id']);

            if ($medicine->stock_quantity < $item['quantity']) {
                throw new \Exception("الكمية غير كافية للدواء: {$medicine->name_ar}");
            }

            $itemTotal = $medicine->consumer_price * $item['quantity'];
            $itemDiscount = ($medicine->discount / 100) * $itemTotal;
            $totalAmount += $itemTotal;
            $totalDiscount += $itemDiscount;

        }

        $netAmount = $totalAmount - $totalDiscount;

        // توليد رقم فاتورة فريد (مثال بسيط - يمكن تحسينه لاحقًا)
        $billNumber = 'BILL-' . time() . '-' . rand(1000, 9999);

        // إنشاء الفاتورة
        $bill = Bill::create([
            'bill_number'     => $billNumber,
            'user_id'         => auth()->id(),
            'total_amount'    => $totalAmount,
            'discount_amount' => $totalDiscount,
            'net_amount'      => $netAmount,
            'status'          => 'pending', // أو يمكن جعله 'approved' تلقائيًا إن رغبت
        ]);

        // إنشاء التفاصيل وتحديث الكمية
        foreach ($request->items as $item) {
            $medicine = Medicine::findOrFail($item['medicine_id']);

            Bill_item::create([
                'bill_id'     => $bill->id,
                'medicine_id' => $medicine->id,
                'quantity'    => $item['quantity'],
                'unit_price'  => $medicine->consumer_price,
                'discount'    => $medicine->discount,
            ]);

            // خصم الكمية من المخزون
            $medicine->stock_quantity -= $item['quantity'];
            $medicine->save();
        }

        DB::commit();

        return response()->json([
            'message' => 'تم إنشاء الفاتورة بنجاح',
            'bill'    => $bill,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 400);
    }
}
public function update(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:pending,approved,rejected',
    ]);

    $bill = Bill::findOrFail($id);
    $bill->status = $request->status;
    $bill->save();

    return response()->json(['message' => 'تم تحديث حالة الفاتورة بنجاح']);
}
public function destroy($id)
{
    $bill = Bill::findOrFail($id);
    $bill->delete();

    return response()->json(['message' => 'تم حذف الفاتورة']);
}
public function get_info_from_bills(){

}
}
