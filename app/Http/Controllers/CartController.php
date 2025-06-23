<?php

namespace App\Http\Controllers;

use App\Models\Cart_items;
use App\Models\Medicine;
use App\Models\supply;
use App\Models\Bill;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Http\Resources\BillResource;
use Illuminate\Http\Request;
use App\Http\Requests\CreateCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
public function store(CreateCartRequest $request)
{
    DB::beginTransaction();

    try {
        // تحقق من صلاحية المستخدم
        if (auth()->user()->role !== 'pharmacist') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // إنشاء السلة
        $cart = Cart::create([
            'user_id' => auth()->id(),
            'customer_name' => $request->customer_name,
            'status' => 'pending',
        ]);

        foreach ($request->items as $item) {
            $itemType = $item['item_type'];
            $itemId   = $item['item_id'];
            $quantity = $item['quantity'];

            // جلب المنتج
            if ($itemType === 'medicine') {
                $product = Medicine::findOrFail($itemId);
                $productName = $product->name_ar;
            } else {
                $product = Supply::findOrFail($itemId);
                $productName = $product->title;
            }

            // حساب الكمية المحجوزة لهذا المنتج في سلال غير مؤكدة
            $reservedQty = Cart_items::whereHas('cart', function ($q) {
                $q->where('status', 'pending');
            })->where('item_type', $itemType)
              ->where('item_id', $itemId)
              ->sum('stock_quantity');

            $availableQty = $product->stock_quantity - $reservedQty;

            if ($quantity > $availableQty) {
                return response()->json([
                    'status' => 400,
                    'message' => "الكمية المطلوبة ($quantity) غير متاحة حالياً لـ {$productName}. المتاح: $availableQty"
                ], 400);
            }

            $price = $product->consumer_price;
            $total = $price * $quantity;

            // تحقق هل العنصر موجود مسبقاً بنفس السلة
            $existingItem = $cart->items()
                ->where('item_type', $itemType)
                ->where('item_id', $itemId)
                ->first();

            if ($existingItem) {
                // ✅ العنصر موجود مسبقاً → تحديث الكمية والسعر الإجمالي
                $existingItem->stock_quantity += $quantity;
                $existingItem->total_price += $total;
                $existingItem->save();
            } else {
                // ❌ عنصر جديد → إنشاء
                $cart->items()->create([
                    'item_type'      => $itemType,
                    'item_id'        => $itemId,
                    'stock_quantity' => $quantity,
                    'unit_price'     => $price,
                    'total_price'    => $total,
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء السلة بنجاح.',
            'data' => new CartResource($cart->load('items'))
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'فشل في إنشاء السلة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function updateCartItem(UpdateCartItemRequest $request, $id)
{
    $cartItem = Cart_items::findOrFail($id);

    if ($cartItem->cart->status !== 'pending') {
        return response()->json([
            'status' => false,
            'message' => 'لا يمكن تعديل عنصر ضمن سلة مؤكدة أو ملغاة.'
        ], 403);
    }

    $cartItem->update([
        'stock_quantity' => $request->stock_quantity,
        'total_price' => $cartItem->unit_price * $request->stock_quantity,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'تم تعديل الكمية بنجاح.',
        'data' => new CartItemResource($cartItem)
    ]);
}
public function deleteCartItem($id)
{
    $cartItem = Cart_items::findOrFail($id);

    if ($cartItem->cart->status !== 'pending') {
        return response()->json([
            'status' => false,
            'message' => 'لا يمكن حذف عنصر من سلة مؤكدة أو ملغاة.'
        ], 403);
    }

    $cartItem->delete();

    return response()->json([
        'status' => true,
        'message' => 'تم حذف العنصر من السلة بنجاح.'
    ]);
}
public function deleteCart($id)
{
    $cart = Cart::with('items')->findOrFail($id);

    if ($cart->status !== 'pending') {
        return response()->json([
            'status' => false,
            'message' => 'لا يمكن حذف سلة مؤكدة أو ملغاة.'
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

    $deleted = Cart::where('user_id', $user->id)->delete();

    return response()->json([
        'status' => true,
        'message' => "تم حذف {$deleted} سلة بنجاح."
    ]);
}
public function confirmCart($id)
{
    // نبدأ معاملة قاعدة بيانات لضمان سلامة العملية
    DB::beginTransaction();

    try {
        // نجلب السلة مع العناصر المرتبطة بها
        $cart = Cart::with('items')->findOrFail($id);

        // إذا السلة مؤكدة مسبقًا، نرجع خطأ
        if ($cart->status !== 'pending') {
            return response()->json([
                'status' => 403,
                'message' => 'السلة مؤكدة أو ملغاة بالفعل.'
            ], 403);
        }

        // إذا السلة فاضية، ما في داعي نأكدها
        if ($cart->items->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'لا يمكن تأكيد سلة فارغة.'
            ], 400);
        }

        // نحسب المبلغ الإجمالي للفاتورة من عناصر السلة
        $totalPrice = $cart->items->sum('total_price');

        // ننشئ الفاتورة الجديدة
        $bill = Bill::create([
            'user_id' => $cart->user_id,
            'customer_name' => $cart->customer_name,
            'total_price' => $totalPrice,
            'status' => 'confirmed',
        ]);

        // نحدث السلة: نربطها بالفاتورة ونغير حالتها إلى "completed"
        $cart->update([
            'bill_id' => $bill->id,
            'status' => 'completed'
        ]);

        // ننقص الكميات من المخزون بحسب كل عنصر في السلة
        foreach ($cart->items as $item) {
            if ($item->item_type === 'medicine') {
                $model = Medicine::find($item->item_id);
            } else {
                $model = Supply::find($item->item_id);
            }

            // إذا وجدنا العنصر في المخزون، ننقص الكمية المطلوبة
            if ($model) {
                $model->decrement('stock_quantity', $item->stock_quantity);
            }
        }

        // نكمل المعاملة ونثبت التغييرات
        DB::commit();

        // نرجع رد JSON يحتوي على بيانات الفاتورة الجديدة
        return response()->json([
            'status' => 200,
            'message' => 'تم تأكيد السلة وتحويلها إلى فاتورة بنجاح.',
            'data' => new BillResource($bill),
        ]);
    } catch (\Exception $e) {
        // إذا صار خطأ، نرجع للوراء ونلغي العملية
        DB::rollBack();

        return response()->json([
            'status' => 500,
            'message' => 'فشل في تأكيد السلة.',
            'error' => $e->getMessage()
        ], 500);
    }
}
public function index()
{
    try {
        if (auth()->user()->role !== 'pharmacist') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // جلب جميع السلال التي أنشأها الصيدلي الحالي
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
                'message' => 'السلة غير موجودة أو لا تملك صلاحية الوصول إليها.'
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
