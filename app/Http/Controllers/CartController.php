<?php

namespace App\Http\Controllers;

use App\Models\Cart_items;
use App\Models\Medicine;
use App\Models\Supply;

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
 /**
     * إنشاء سلة جديدة مع عناصر (دواء أو مستلزم طبي)
     */

     /**
     * إنشاء سلة جديدة مع عناصر
     */
    /*
    public function store(CreateCartRequest $request)
    {
        DB::beginTransaction();

        try {
            // التحقق من أن المستخدم هو صيدلي
            if (auth()->user()->role !== 'pharmacist') {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            // إنشاء السلة الجديدة
            $cart = Cart::create([
                'user_id' => auth()->id(),
                'customer_name' => $request->customer_name,
                'status' => 'pending',
            ]);

            // معالجة كل عنصر مدخل في السلة
            foreach ($request->items as $item) {
                $itemType = $item['item_type']; // نوع العنصر (medicine أو supply)
                $itemId   = $item['item_id'];   // معرف العنصر
                $quantity = $item['quantity'];  // الكمية المطلوبة

                // تحديد الكلاس المناسب حسب نوع العنصر
                $modelClass = $itemType === 'medicine' ? Medicine::class : Supply::class;
                $item_type_for_db = $itemType; // تخزين الاسم فقط في قاعدة البيانات

                // جلب العنصر من قاعدة البيانات
                $product = $modelClass::findOrFail($itemId);
                $productName = $itemType === 'medicine' ? $product->name_ar : $product->title;

                // حساب الكمية المحجوزة مسبقاً في سلال قيد الانتظار
                $reservedQty = Cart_items::whereHas('cart', fn ($q) => $q->where('status', 'pending'))
                    ->where('item_type', $item_type_for_db)
                    ->where('item_id', $itemId)
                    ->sum('stock_quantity');

                // حساب الكمية المتاحة فعليًا
                $availableQty = $product->stock_quantity - $reservedQty;

                // التحقق من توفر الكمية المطلوبة
                if ($quantity > $availableQty) {
                    return response()->json([
                        'status' => 400,
                        'message' => "الكمية المطلوبة ($quantity) غير متاحة حالياً لـ {$productName}. المتاح: $availableQty"
                    ], 400);
                }

                $price = $product->consumer_price; // سعر الوحدة
                $total = $price * $quantity;      // السعر الإجمالي

                // التحقق إذا كان العنصر موجود مسبقًا في السلة لتحديثه
                $existingItem = $cart->items()
                    ->where('item_type', $item_type_for_db)
                    ->where('item_id', $itemId)
                    ->first();

                if ($existingItem) {
                    $existingItem->stock_quantity += $quantity;
                    $existingItem->total_price += $total;
                    $existingItem->save();
                } else {
                    // إضافة عنصر جديد للسلة
                    $cart->items()->create([
                        'item_type'      => $item_type_for_db,
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
                'data' => new CartResource($cart->load('items')),
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
*/
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
            ->where('status', 'pending')
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
                ->where('status', 'pending')
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
                ->where('status', 'pending')
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
                ->where('status', 'pending')
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


 /**
     * حذف عنصر من السلة
     */
    public function deleteCartItem($id)
    {
        try {
            // جلب العنصر من جدول عناصر السلة
            $cartItem = Cart_items::findOrFail($id);

            // منع الحذف في حال كانت السلة مؤكدة أو ملغاة
            if ($cartItem->cart->status !== 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن حذف عنصر من سلة مؤكدة أو ملغاة.'
                ], 403);
            }

            // حذف العنصر من السلة
            $cartItem->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف العنصر من السلة بنجاح.'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'العنصر غير موجود في السلة.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء محاولة حذف العنصر.',
                'error' => $e->getMessage()
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

    $deleted = Cart::where('user_id', $user->id)
                   ->where('status', 'pending')
                   ->delete();

    return response()->json([
        'status' => true,
        'message' => "تم حذف {$deleted} سلة (معلقة) بنجاح."
    ]);
}
 /**
     * تأكيد السلة وتحويلها إلى فاتورة
     */
    public function confirmCart2($id)
    {
        DB::beginTransaction();

        try {
            // جلب السلة مع العناصر
            $cart = Cart::with('items')->findOrFail($id);

            // التحقق من أن السلة ما زالت قيد الانتظار
            if ($cart->status !== 'pending') {
                return response()->json([
                    'status' => 403,
                    'message' => 'السلة مؤكدة أو ملغاة بالفعل.'
                ], 403);
            }

            // منع تأكيد سلة فارغة
            if ($cart->items->isEmpty()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'لا يمكن تأكيد سلة فارغة.'
                ], 400);
            }

            // حساب إجمالي السعر
            $totalAmount = $cart->items->sum('total_price');

            // إنشاء الفاتورة بوضعية "معلقة"
            $bill = Bill::create([
                'user_id' => $cart->user_id,
                'customer_name' => $cart->customer_name,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // تحديث حالة السلة وربطها بالفاتورة
            $cart->update([
                'bill_id' => $bill->id,
                'status' => 'completed',
            ]);

            // تحديث المخزون
            foreach ($cart->items as $item) {
                $model = null;

                if ($item->item_type === 'medicine') {
                    $model = Medicine::find($item->item_id);
                } elseif ($item->item_type === 'supply') {
                    $model = Supply::find($item->item_id);
                }

                if ($model) {
                    $model->decrement('stock_quantity', $item->stock_quantity);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'تم تأكيد السلة وتحويلها إلى فاتورة بنجاح.',
                'data' => new BillResource($bill),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'فشل في تأكيد السلة.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

public function confirmAllPendingCarts()
{
    DB::beginTransaction();

    try {
        // جلب جميع السلال المعلقة
        $carts = Cart::with('items')->where('status', 'pending')->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'لا توجد سلال معلقة للتأكيد.'
            ], 400);
        }

        foreach ($carts as $cart) {
            // منع تأكيد سلة فارغة
            if ($cart->items->isEmpty()) {
                // يمكن تتجاهل السلة الفارغة أو تحطها في لوج حسب رغبتك
                continue;
            }

            // حساب إجمالي السعر
            $totalAmount = $cart->items->sum('total_price');

            // إنشاء الفاتورة بوضعية "pending" (معلقة)
            $bill = Bill::create([
                'user_id' => $cart->user_id,
                'customer_name' => $cart->customer_name,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            // تحديث حالة السلة وربطها بالفاتورة
            $cart->update([
                'bill_id' => $bill->id,
                'status' => 'completed',
            ]);

            // تحديث المخزون لكل عنصر في السلة
            foreach ($cart->items as $item) {
                $model = null;

                if ($item->item_type === 'medicine') {
                    $model = Medicine::find($item->item_id);
                } elseif ($item->item_type === 'supply') {
                    $model = Supply::find($item->item_id);
                }

                if ($model) {
                    $model->decrement('stock_quantity', $item->stock_quantity);
                }
            }
        }

        DB::commit();

        return response()->json([
            'status' => 200,
            'message' => 'تم تأكيد جميع السلال المعلقة وتحويلها إلى فواتير بنجاح.'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => 500,
            'message' => 'فشل في تأكيد السلال.',
            'error' => $e->getMessage()
        ], 500);
    }
}



    /**
     * استعراض جميع السلال للصيدلي الحالي
     */
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
