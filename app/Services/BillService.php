<?php 
namespace App\Services;

use App\Models\Bill;
use App\Http\Resources\BillResource;

class BillService
{
    public static function sendAllPendingBills($user)
    {
        $bills = Bill::where('user_id', $user->id)
                     ->where('status', 'pending')
                     ->get();

        foreach ($bills as $bill) {
            $bill->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return BillResource::collection($bills);
    }
}
