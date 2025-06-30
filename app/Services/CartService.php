<?php 
namespace App\Services;

use App\Models\Cart;
use App\Models\Medicine;
use App\Models\Supply;
use App\Models\Bill;
use App\Http\Resources\BillResource;
use Illuminate\Support\Facades\DB;

class CartService
{
    public static function confirmAllPendingCarts($user)
    {
        $pendingCarts = Cart::with('items')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        $confirmedBills = [];

        foreach ($pendingCarts as $cart) {
            if ($cart->items->isEmpty()) continue;

            $totalAmount = $cart->items->sum('total_price');

            $bill = Bill::create([
                'user_id' => $user->id,
                'customer_name' => $cart->customer_name,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            $cart->update([
                'status' => 'completed',
                'bill_id' => $bill->id,
            ]);

            foreach ($cart->items as $item) {
                $model = $item->item_type === 'medicine'
                    ? Medicine::find($item->item_id)
                    : Supply::find($item->item_id);

                if ($model) {
                    $model->decrement('stock_quantity', $item->stock_quantity);
                }
            }

            $confirmedBills[] = new BillResource($bill);
        }

        return $confirmedBills;
    }
}
