<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
 use App\Models\Bill;
 use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class ShiftController extends Controller
{
   // بدء وردية
    public function start(Request $request)
    {
        $user = Auth::user();

        $active = Shift::where('user_id', $user->id)->where('status', 'active')->first();
        if ($active) {
            return response()->json(['message' => 'لديك وردية مفتوحة بالفعل.'], 400);
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'start_time' => Carbon::now(),
            'status' => 'active',
        ]);

        return response()->json(['message' => 'تم بدء الوردية بنجاح.', 'shift' => $shift] , 201);
    }
    // إنهاء وردية
    public function end(Request $request)
    {
        $user = Auth::user();
        $shift = Shift::where('user_id', $user->id)->where('status', 'active')->first();

        if (! $shift) {
            return response()->json(['message' => 'لا توجد وردية نشطة لإنهائها.'], 404);
        }

        $totalSales = Bill::where('user_id', $user->id)
            ->whereBetween('created_at', [$shift->start_time, Carbon::now()])
            ->sum('total_amount');

        $shift->update([
            'end_time' => Carbon::now(),
            'total_sales' => $totalSales,
            'status' => 'closed',
        ]);

        return response()->json(['message' => 'تم إنهاء الوردية.', 'shift' => $shift] , 200 );
    }
    // جلب الورديات الخاصة بالمستخدم
    public function userShifts()
    {
        $user = Auth::user();
        return response()->json(Shift::where('user_id', $user->id)->latest()->get());
    }
    // الورديّة الحالية
    public function current()
    {
        $user = Auth::user();
        $shift = Shift::where('user_id', $user->id)->where('status', 'active')->first();
        return response()->json(['shift' => $shift]);
    }
    // ملخص وردية محددة
    public function summary($id)
    {
        $shift = Shift::with('user')->findOrFail($id);

        $bills = Bill::where('user_id', $shift->user_id)
            ->whereBetween('created_at', [$shift->start_time, $shift->end_time ?? now()])
            ->get();

        return response()->json([
            'shift_id' => $shift->id,
            'user' => $shift->user->name,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'total_sales' => $shift->total_sales,
            'total_bills' => $bills->count(),
        ] , 200);
    }
}
