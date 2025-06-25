<?php

namespace App\Http\Controllers;

use App\Models\supply;
use App\Http\Resources\SupplyResource;
use App\Http\Requests\SearchSupplyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplyController extends Controller
{
    //عرض المستلزمات من \ون تصنيف
    public function index()
{
    $supplies = Supply::select('title', 'consumer_price', 'stock_quantity', 'image_url')->get();

    return response()->json([
        'data' => $supplies
    ]);
}
    // استعراض مستلزمات مرتبطة بصنف
    public function getByCategory($categoryId)
{
    $supplies = Supply::where('category_id', $categoryId)->get();

    return response()->json([
        'status' => true,
        'message' => 'تم جلب المستلزمات الطبية بنجاح.',
        'data' => SupplyResource::collection($supplies)
    ], 200);
}

    // استعراض تفاصيل مستلزم طبي
   public function show($id)
{
    $supply = Supply::find($id);

    if (!$supply) {
        return response()->json([
            'status' => false,
            'message' => 'لم يتم العثور على المستلزم.',
            'data' => null
        ], 404);
    }

    return response()->json([
        'status' => true,
        'message' => 'تم جلب تفاصيل المستلزم.',
        'data' => new SupplyResource($supply)
    ], 200);
}
//البحث عن مستلزم من خلال الاسم / الكمية
public function search(SearchSupplyRequest $request)
    {
        $query = Supply::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('stock_quantity')) {
            $query->where('stock_quantity', '>=', $request->stock_quantity);
        }

        $results = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'نتائج البحث عن المستلزمات:',
            'data' => SupplyResource::collection($results)
        ]);
}

public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',
        'pharmacy_price' => 'required|numeric',
        'consumer_price' => 'required|numeric',
        'discount' => 'nullable|numeric',
        'stock_quantity' => 'required|integer',
        'image_url' => 'nullable' // ممكن رابط أو ملف
    ]);

    \Log::info('image_url content:', ['value' => $request->image_url]);

    $imagePath = null;

    // إذا كانت صورة مرفوعة
    if ($request->hasFile('image_url')) {
        $imageName = Str::random(20) . '.' . $request->file('image_url')->extension();
        $request->file('image_url')->move(public_path('images/supplies'), $imageName);
        $imagePath = 'images/supplies/' . $imageName;
    }
    // إذا كان رابط URL
    elseif ($request->filled('image_url') && filter_var($request->image_url, FILTER_VALIDATE_URL)) {
        $imagePath = $request->image_url;
    }

    $supply = Supply::create([
        'title' => $request->title,
        'category_id' => $request->category_id,
        'pharmacy_price' => $request->pharmacy_price,
        'consumer_price' => $request->consumer_price,
        'discount' => $request->discount,
        'stock_quantity' => $request->stock_quantity,
        'image_url' => $imagePath,
    ]);

    return response()->json([
        'message' => 'تمت إضافة المستلزم الطبي بنجاح',
        'data' => $supply
    ], 201);
}



public function update(Request $request, $id)
{
    $supply = Supply::findOrFail($id);

    $request->validate([
        'title' => 'sometimes|string|max:255',
        'category_id' => 'sometimes|exists:categories,id',
        'pharmacy_price' => 'sometimes|numeric',
        'consumer_price' => 'sometimes|numeric',
        'discount' => 'nullable|numeric',
        'stock_quantity' => 'sometimes|integer',
        'image_url' => 'nullable|url'  // ✅ فقط رابط
    ]);

    // تحديث الحقول فقط إذا تم إرسالها
    $supply->update($request->only([
        'title',
        'category_id',
        'pharmacy_price',
        'consumer_price',
        'discount',
        'stock_quantity',
        'image_url'
    ]));

    return response()->json([
        'message' => '✅ تم تحديث المستلزم الطبي بنجاح',
        'data' => $supply
    ]);
}
public function destroy($id)
{
    try {
        $supply = Supply::findOrFail($id);
        $supply->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المستلزم الطبي بنجاح.',
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => false,
            'message' => 'المستلزم الطبي غير موجود.',
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء الحذف.',
            'error' => $e->getMessage()
        ], 500);
    }
}




}
