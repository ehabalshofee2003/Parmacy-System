<?php

namespace App\Http\Controllers;

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
 public function createNewCart(Request $request)
    {
        $cart = Cart::create([
            'user_id' => auth()->id(),
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء سلة جديدة.',
            'cart_id' => $cart->id
        ]);
    }
    // 🟢 إضافة عنصر للسلة
    public function addItemToCart(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'item_type' => 'required|in:medicine,supply',
            'item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('id', $request->cart_id)
             ->whereIn('status', ['pending', 'completed'])
            ->firstOrFail();

        $modelClass = $request->item_type === 'medicine' ? Medicine::class : Supply::class;
        $product = $modelClass::findOrFail($request->item_id);

        $reservedQty = $cart->items()
            ->where('item_type', $request->item_type)
            ->where('item_id', $request->item_id)
            ->sum('stock_quantity');

        $availableQty = $product->stock_quantity - $reservedQty;

        if ($request->quantity > $availableQty) {
            return response()->json([
                'status' => false,
                'message' => 'الكمية المطلوبة غير متاحة. المتاح: ' . $availableQty
            ], 400);
        }

        $price = $product->consumer_price;
        $total = $price * $request->quantity;

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

        return response()->json([
            'status' => true,
            'message' => 'تمت إضافة العنصر إلى السلة.',
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
            return response()->json(['status' => false, 'message' => 'لا توجد سلة حالياً.']);
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
            ->firstOrFail();

        $cart->status = 'completed';
        $cart->save();

        return response()->json([
            'status' => true,
            'message' => 'تم تأكيد السلة.'
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
                ->firstOrFail();

    $cart->customer_name = $request->customer_name;
    $cart->save();

    return response()->json([
        'status' => true,
        'message' => 'تم تحديث اسم الزبون بنجاح.'
    ]);
    }
//تعديل كمية عنصر في السلة
public function updateCartItemQuantity(Request $request)
{
    $request->validate([
        'cart_id'      => 'required|integer',
        'item_type'    => 'required|in:medicine,supply',
        'item_id'      => 'required|integer',
        'new_quantity' => 'required|integer|min:1',
    ]);

    // ✅ محاولة جلب السلة
    $cart = Cart::where('id', $request->cart_id)
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'completed'])
                ->first();

    if (!$cart) {
        return response()->json([
            'status' => false,
            'message' => 'السلة غير موجودة أو لا يمكن التعديل عليها حالياً.'
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
            'message' => 'العنصر غير موجود في السلة.'
        ], 404);
    }

    // ✅ محاولة جلب المنتج الأصلي (دواء أو مستلزم)
    $modelClass = $request->item_type === 'medicine' ? Medicine::class : Supply::class;
    $product = $modelClass::find($request->item_id);

    if (!$product) {
        return response()->json([
            'status' => false,
            'message' => 'العنصر المطلوب غير موجود في قاعدة البيانات.'
        ], 404);
    }

    // ✅ حساب الكمية المتاحة
    $reservedQty = $cart->items()
                        ->where('item_type', $request->item_type)
                        ->where('item_id', $request->item_id)
                        ->sum('stock_quantity');

    $availableQty = $product->stock_quantity - ($reservedQty - $item->stock_quantity);

    if ($request->new_quantity > $availableQty) {
        return response()->json([
            'status' => false,
            'message' => 'الكمية المطلوبة غير متاحة. المتاح: ' . $availableQty
        ], 400);
    }

    // ✅ تحديث الكمية والسعر
    $item->stock_quantity = $request->new_quantity;
    $item->total_price = $item->unit_price * $request->new_quantity;
    $item->save();

    return response()->json([
        'status' => true,
        'message' => 'تم تعديل الكمية بنجاح.'
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
            'message' => 'السلة غير موجودة أو لا يمكن التعديل عليها حالياً.'
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
            'message' => 'العنصر غير موجود في السلة.'
        ], 404);
    }

    // ✅ حذف العنصر
    $item->delete();

    return response()->json([
        'status' => true,
        'message' => 'تم حذف العنصر من السلة.'
    ]);
   }



public function deleteCart($id)
{
    $cart = Cart::with('items')->findOrFail($id);

    if (!in_array($cart->status, ['pending', 'completed'])) {
        return response()->json([
            'status' => false,
            'message' => 'لا يمكن حذف هذه السلة.'
        ], 403);
    }

    $cart->delete();

    return response()->json([
        'status' => true,
        'message' => 'تم حذف السلة بنجاح.'
    ]);
}

public function deleteAllCartsForCurrentPharmacist()
{
    $user = auth()->user();

    $deleted = Cart::where('user_id', $user->id)
                   ->whereIn('status', ['pending', 'completed'])

                   ->delete();

    return response()->json([
        'status' => true,
        'message' => "تم حذف {$deleted} سلة (معلقة) بنجاح."
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


    // إنشاء الفاتورة
    $bill = Bill::create([
        'user_id'      => auth()->id(),
        'total_amount' => $total,
        'status'       => 'pending',
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

        // إنشاء الفاتورة
        $bill = Bill::create([
            'user_id' => $userId,
            'total_amount' => $total,
            'status' => 'pending',
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
                'message' => 'تم جلب السلال بنجاح.',
                'data' => CartResource::collection($carts),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'حدث خطأ أثناء جلب السلال.',
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
                'message' => 'السلة غير موجودة.'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب تفاصيل السلة بنجاح.',
            'data' => new CartResource($cart)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'حدث خطأ أثناء جلب تفاصيل السلة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
/*
 خطوات إنشاء السلة (من الـ API)
الخطوة 1️⃣: الصيدلي يحدد اسم الزبون
الخطوة 2️⃣: يختار عناصر السلة:
نوع كل عنصر (medicine أو supply)

رقم العنصر

الكمية

الخطوة 3️⃣: السيرفر يقوم بـ:
إنشاء سطر جديد في جدول carts

إدخال كل عنصر في جدول cart_items

جلب السعر من جدول drugs أو supplies

حساب السعر الإجمالي unit_price × quantity

إرجاع الـ JSON المنسق مع التفاصيل
*/
