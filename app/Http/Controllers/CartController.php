<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicineResource;
use App\Models\Cart_items;
use App\Models\Medicine;
use App\Models\Supply;
use App\Models\Bill_item;
use App\Models\Bill;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\BillResource;
use Illuminate\Http\Request;
use App\Http\Requests\CreateCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CartController extends Controller
{

// 🟢 إنشاء سلة جديدة فارغة
public function createNewCart(Request $request)    {
        $cart = Cart::create([
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cart ' . $cart->bill_number . ' Created Successfully',
            'cart_id' => $cart->id,
            'cart_number' =>  $cart->bill_number
        ]);
}
// 🟢 إضافة عنصر للسلة
public function addItemToCart(Request $request)
{
    $request->validate([
        'cart_id'   => 'required|exists:carts,id',
        'item_type' => 'required|in:medicine,supply',
        'item_id'   => 'required|integer',
        'quantity'  => 'required|integer|min:1',
    ]);

    // ✅ جلب السلة
    $cart = Cart::where('id', $request->cart_id)
                ->whereIn('status', ['pending', 'completed'])
                ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'The Cart Does\'t Found'
        ], 404);
    }

    // ✅ تحديد نوع العنصر
    $modelClass = $request->item_type === 'medicine' ? Medicine::class : Supply::class;
    $product = $modelClass::find($request->item_id);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'The Item Does\'t Found'
        ], 404);
    }

    // ✅ التحقق من الكمية المتوفرة
    if ($request->quantity > $product->stock_quantity) {
        return response()->json([
            'status' => false,
            'message' => 'The requested quantity is not available. Available: ' . $product->stock_quantity
        ], 400);
    }

    // ✅ حساب السعر
    $price = $product->consumer_price;
    $total = $price * $request->quantity;

    // ✅ إضافة أو تعديل العنصر في السلة
    $existingItem = $cart->items()
                         ->where('item_type', $request->item_type)
                         ->where('item_id', $request->item_id)
                         ->first();

    if ($existingItem) {
        $existingItem->stock_quantity += $request->quantity;
        $existingItem->total_price += $total;
        $existingItem->save();
    } else {
        $cart->items()->create([
            'item_type'      => $request->item_type,
            'item_id'        => $request->item_id,
            'stock_quantity' => $request->quantity,
            'unit_price'     => $price,
            'total_price'    => $total,
        ]);
    }

    // ✅ خصم الكمية من المخزون
    $product->stock_quantity -= $request->quantity;
    $product->save();

    return response()->json([
        'status' => true,
        'message' => 'Item added to cart successfully',
       'data' => new MedicineResource($product)
    ]);
}
// 🟢 عرض السلة الحالية
public function getCurrentCart()
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->with('items.medicine', 'items.supply')
            ->latest()
            ->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'There is no cart yet']);
        }

        return response()->json([
            'status' => true,
            'data' => new CartResource($cart),
        ]);
}
// 🟢 تأكيد السلة الحالية
public function confirmCart(Request $request)
{
    $request->validate(['cart_id' => 'required|exists:carts,id']);

    $cart = Cart::where('id', $request->cart_id)
        ->where('user_id', auth()->id())
        ->where('status', 'pending')
        ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'The cart does not exist or was previously completed'
        ], 404);
    }

    $cart->status = 'completed';
    $cart->save();

    return response()->json([
        'status' => true,
        'message' => 'Cart confirmed successfully'
    ]);
}
//  تعديل اسم الزبون في السلة
public function updateCartName(Request $request)
{
    $request->validate([
        'cart_id' => 'required|exists:carts,id',
        'customer_name' => 'nullable|string|max:255'
    ]);

    $cart = Cart::where('id', $request->cart_id)
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'completed'])
                ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'the cart does not found'
        ], 404);
    }

    $cart->customer_name = $request->customer_name;
    $cart->save();

    return response()->json([
        'status' => true,
        'message' => 'The customer\'s name has been successfully updated.'
    ]);
}
//زيادة كمية العنصر
public function increaseQuantity(Request $request)
{
    $request->validate([
        'cart_id'   => 'required|integer',
        'item_type' => 'required|in:medicine,supply',
        'item_id'   => 'required|integer',
    ]);

    $cart = Cart::where('id', $request->cart_id)
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'completed'])
                ->first();

    if (!$cart) {
        return response()->json(['status' => false, 'message' => 'the cart does\'t found'], 404);
    }

    $item = $cart->items()
                 ->where('item_type', $request->item_type)
                 ->where('item_id', $request->item_id)
                 ->first();

    if (!$item) {
        return response()->json(['status' => false, 'message' => 'the item does\'t found in the cart'], 404);
    }

    $modelClass = $request->item_type === 'medicine' ? Medicine::class : Supply::class;
    $product = $modelClass::find($request->item_id);

    if (!$product) {
        return response()->json(['status' => false, 'message' => 'العنصر غير موجود'], 404);
    }

    if ($product->stock_quantity < 1) {
        return response()->json([
            'status' => false,
            'message' => 'The quantity cannot be increased; stock is insufficient. Available: 0'
        ], 400);
    }

    // ✅ تعديل الكمية
    $item->stock_quantity += 1;
    $item->total_price = $item->stock_quantity * $item->unit_price;
    $item->save();

    // ✅ خصم 1 من المخزون
    $product->stock_quantity -= 1;
    $product->save();

    return response()->json([
        'status' => true,
        'message' => 'The quantity has been successfully increased.',
        'data' => [
            'stock_quantity' => $item->stock_quantity,
            'total_price' => $item->total_price,
            'item_remaining_stock' => $product->stock_quantity
        ]
    ]);
}
//تقليل كمية العنصر
public function decreaseQuantity(Request $request)
{
    $request->validate([
        'cart_id'   => 'required|integer',
        'item_type' => 'required|in:medicine,supply',
        'item_id'   => 'required|integer',
    ]);

    $cart = Cart::where('id', $request->cart_id)
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'completed'])
                ->first();

    if (!$cart) {
        return response()->json(['status' => false, 'message' => 'السلة غير موجودة'], 404);
    }

    $item = $cart->items()
                 ->where('item_type', $request->item_type)
                 ->where('item_id', $request->item_id)
                 ->first();

    if (!$item) {
        return response()->json(['status' => false, 'message' => 'العنصر غير موجود في السلة'], 404);
    }

    if ($item->stock_quantity <= 1) {
        return response()->json([
            'status' => false,
            'message' => 'The quantity cannot be reduced to less than 1.'
        ], 400);
    }

    $modelClass = $request->item_type === 'medicine' ? Medicine::class : Supply::class;
    $product = $modelClass::find($request->item_id);

    if (!$product) {
        return response()->json(['status' => false, 'message' => 'العنصر غير موجود'], 404);
    }

    // ✅ تعديل الكمية في السلة
    $item->stock_quantity -= 1;
    $item->total_price = $item->stock_quantity * $item->unit_price;
    $item->save();

    // ✅ إعادة 1 للمخزون
    $product->stock_quantity += 1;
    $product->save();

    return response()->json([
        'status' => true,
        'message' => 'The quantity has been successfully reduced.',
        'data' => [
            'stock_quantity' => $item->stock_quantity,
            'total_price' => $item->total_price,
            'item_remaining_stock' => $product->stock_quantity
        ]
    ]);
}
//حذف عنصر من السلة
public function removeCartItem(Request $request)
{
    $request->validate([
        'cart_id'   => 'required|integer',
        'item_type' => 'required|in:medicine,supply',
        'item_id'   => 'required|integer',
    ]);

    // ✅ جلب السلة بدون firstOrFail
    $cart = Cart::where('id', $request->cart_id)
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'completed'])
                ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'the cart doesn\'t found'
        ], 404);
    }

    // ✅ محاولة جلب العنصر من السلة
    $item = $cart->items()
                 ->where('item_type', $request->item_type)
                 ->where('item_id', $request->item_id)
                 ->first();

    if (!$item) {
        return response()->json([
            'status' => false,
            'message' => 'The item is not in the cart.'
        ], 404);
    }

    // ✅ حذف العنصر
        $item->delete();
    if ($request->item_type === 'medicine') {
        Medicine::where('id', $request->item_id)
            ->increment('stock_quantity', $item->stock_quantity);
    } elseif ($request->item_type === 'supply') {
        Supply::where('id', $request->item_id)
            ->increment('stock_quantity', $item->stock_quantity);
    }
    return response()->json([
        'status' => true,
        'message' => 'The item has been removed from the cart.'
    ]);

}
public function deleteCart($id)
{
    $cart = Cart::with('items')->find($id);

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'the cart doesn\'t found'
        ], 404);
    }

    if (!in_array($cart->status, ['pending', 'completed'])) {
        return response()->json([
            'status' => false,
            'message' => 'This cart cannot be deleted.'
        ], 403);
    }

    // ✅ إعادة الكميات للمخزون قبل الحذف
    foreach ($cart->items as $item) {
        if ($item->item_type === 'medicine') {
            Medicine::where('id', $item->item_id)
                ->increment('stock_quantity', $item->stock_quantity);
        } elseif ($item->item_type === 'supply') {
            Supply::where('id', $item->item_id)
                ->increment('stock_quantity', $item->stock_quantity);
        }
    }

    // ✅ حذف السلة (سيحذف العناصر أيضًا إذا العلاقة مضبوط عليها cascade)
    $cart->delete();

    return response()->json([
        'status' => true,
        'message' => 'The cart was successfully deleted, and items were returned to stock.'
    ]);
}

public function deleteAllCartsForCurrentPharmacist()
{
    $user = auth()->user();

    // ✅ جلب جميع السلات مع عناصرها
    $carts = Cart::with('items')
        ->where('user_id', $user->id)
        ->whereIn('status', ['pending', 'completed'])
        ->get();

    if ($carts->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No carts found to delete.'
        ], 404);
    }

    // ✅ إرجاع الكميات للمخزون
    foreach ($carts as $cart) {
        foreach ($cart->items as $item) {
            if ($item->item_type === 'medicine') {
                Medicine::where('id', $item->item_id)
                    ->increment('stock_quantity', $item->stock_quantity);
            } elseif ($item->item_type === 'supply') {
                Supply::where('id', $item->item_id)
                    ->increment('stock_quantity', $item->stock_quantity);
            }
        }
    }

    // ✅ حذف كل السلات
    $deleted = Cart::where('user_id', $user->id)
        ->whereIn('status', ['pending', 'completed'])
        ->delete();

    return response()->json([
        'status' => true,
        'message' => "Successfully deleted {$deleted} carts and returned items to stock."
    ]);
}

// تأكيد السلة وتحويلها إلى فاتورة
public function convertCartToBill(Request $request)
{
    $request->validate([
        'cart_id' => 'required|exists:carts,id',
    ]);

    // جلب السلة المكتملة فقط للمستخدم الحالي
    $cart = Cart::with('items')
        ->where('id', $request->cart_id)
        ->where('user_id', auth()->id())
        ->where('status', 'completed')
        ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'The cart cannot be confirmed. It may be pre-confirmed or incomplete',
        ], 400);
    }

    $total = 0;
    $billItems = [];

    foreach ($cart->items as $cartItem) {
        if ($cartItem->item_type === 'medicine') {
            $product = Medicine::find($cartItem->item_id);
        } elseif ($cartItem->item_type === 'supply') {
            $product = Supply::find($cartItem->item_id);
        } else {
            continue;
        }

        if (!$product || is_null($cartItem->stock_quantity)) {
            continue;
        }

        $unitPrice = $product->consumer_price ?? 0;
        $itemTotal = $unitPrice * $cartItem->stock_quantity;
        $total += $itemTotal;

        // خصم الكمية من المخزون
        $product->stock_quantity -= $cartItem->stock_quantity;
        if ($product->stock_quantity < 0) {
            $product->stock_quantity = 0;
        }
        $product->save();

        $billItems[] = [
            'item_type'      => $cartItem->item_type,
            'item_id'        => $cartItem->item_id,
            'stock_quantity' => $cartItem->stock_quantity,
            'unit_price'     => $unitPrice,
            'total_price'    => $itemTotal,
        ];
    }

    $lastBill = Bill::orderBy('bill_number', 'desc')->first();
    $lastNumber = $lastBill ? intval($lastBill->bill_number) : 0;
    $nextBillNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

    // إنشاء الفاتورة
    $bill = Bill::create([
        'user_id'      => auth()->id(),
        'total_amount' => $total,
        'status'       => 'pending',
        'bill_number'  => $nextBillNumber,
    ]);

    // حفظ عناصر الفاتورة مع bill_id
    foreach ($billItems as $item) {
        Bill_item::create(array_merge($item, ['bill_id' => $bill->id]));
    }

    // تأكيد السلة
    $cart->status = 'confirmed';
    $cart->save();

    return response()->json([
        'status'          => true,
        'message'         => 'The Bill was created and the Cart was successfully confirmed.',
        'bill_id'         => $bill->id,
        'bill_number'     => $bill->bill_number,
        'cart_bill_number'=> $cart->bill_number,
    ]);
}

public function confirmAllCompletedCarts()
{
    $userId = auth()->id();

    // جلب كل السلات المكتملة للمستخدم
    $carts = Cart::with('items')
        ->where('user_id', $userId)
        ->where('status', 'completed')
        ->get();

    if ($carts->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'There are no completed Carts to convert.',
        ], 404);
    }

    $convertedBills = [];

    foreach ($carts as $cart) {
        $total = 0;
        $billItems = [];

        foreach ($cart->items as $cartItem) {
            if ($cartItem->item_type === 'medicine') {
                $product = Medicine::find($cartItem->item_id);
            } elseif ($cartItem->item_type === 'supply') {
                $product = Supply::find($cartItem->item_id);
            } else {
                continue;
            }

            if (!$product || is_null($cartItem->stock_quantity)) {
                continue;
            }

            $unitPrice = $product->price ?? 0;
            $itemTotal = $unitPrice * $cartItem->stock_quantity;
            $total += $itemTotal;

            $billItems[] = [
                'item_type' => $cartItem->item_type,
                'item_id' => $cartItem->item_id,
                'stock_quantity' => $cartItem->stock_quantity,
                'unit_price' => $unitPrice,
                'total_price' => $itemTotal,
            ];
        }

       // توليد bill_number فريد
$lastBill = Bill::orderBy('bill_number', 'desc')->first();
$lastNumber = $lastBill ? intval($lastBill->bill_number) : 0;
$nextBillNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

// إنشاء الفاتورة
$bill = Bill::create([
    'user_id'      => $userId,
    'total_amount' => $total,
    'status'       => 'pending',
    'bill_number'  => $nextBillNumber,
]);

        // حفظ تفاصيل الفاتورة
        foreach ($billItems as $item) {
            $item['bill_id'] = $bill->id;
            Bill_item::create($item);
        }

        // تحديث حالة السلة إلى confirmed
        $cart->status = 'confirmed';
        $cart->save();

        $convertedBills[] = [
            'cart_id' => $cart->id,
            'cart_bill_number' => $cart->bill_number,
            'bill_id' => $bill->id,
            'bill_number' => $bill->bill_number,
        ];
    }

    return response()->json([
        'status' => true,
        'message' => 'All completed Carts have been confirmed and converted to Bills.',
        'converted_bills' => $convertedBills,
    ]);
}
public function sendToAdmin($id)
{
    $bill = Bill::findOrFail($id);

    // تحقق من أن الفاتورة مؤكدة مسبقاً
    if ($bill->status !== 'confirmed') {
        return response()->json([
            'status' => false,
            'message' => 'Unconfirmed Bill cannot be sent.'
        ], 400);
    }

    // تحقق هل تم إرسالها مسبقاً
    if ($bill->sent_to_admin) {
        return response()->json([
            'status' => false,
            'message' => 'This Bill has already been sent to Adman.'
        ], 400);
    }

    // تحديث حالة الإرسال
    $bill->sent_to_admin = true;
    $bill->save();

    return response()->json([
        'status' => true,
        'message' => 'The invoice has been successfully sent to the Admin',
        'data' => $bill
    ]);
}

public function index()
    {
        try {
            if (auth()->user()->role !== 'pharmacist') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $carts = Cart::with('items')
                        ->where('user_id', auth()->id())
                        ->orderBy('created_at', 'desc')
                        ->get();

            return response()->json([
                'status' => 200,
                'message' => 'The Carts were successfully retrieved.',
                'data' => CartResource::collection($carts),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while fetching the Carts.',
                'error' => $e->getMessage(),
            ], 500);
        }
}

public function show($id)
{
    try {
        if (auth()->user()->role !== 'pharmacist') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // البحث عن السلة التي تخص هذا الصيدلي فقط
        $cart = Cart::with('items')
                    ->where('user_id', auth()->id())
                    ->where('id', $id)
                    ->first();

        if (!$cart) {
            return response()->json([
                'status' => 404,
                'message' => 'The cart does\'t found'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'The cart details have been successfully retrieved.',
            'data' => new CartResource($cart)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'An error occurred while retrieving the cart details.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
