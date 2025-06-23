<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\SearchDrugRequest;
use App\Http\Requests\UpdateMedicineRequest;
use App\Http\Resources\MedicineResource;
use App\Http\Resources\DrugResource;
class MedicineController extends Controller
{

// البحث عن دواء من خلال الاسم  / رقم الباركود /
public function search(SearchDrugRequest $request)
    {
        $query = Medicine::query();

        if ($request->filled('barcode')) {
            $query->where('barcode', $request->barcode);
        }

        if ($request->filled('name_en')) {
            $query->where('name_en', 'like', '%' . $request->title . '%');
        }
         if ($request->filled('name_ar')) {
            $query->where('name_ar', 'like', '%' . $request->title . '%');
        }

        if ($request->filled('stock_quantity')) {
            $query->where('stock_quantity', '>=', $request->stock_quantity);
        }

        if ($request->filled('expiry_date')) {
            $query->where('expiry_date', $request->expiry_date);
        }

        $results = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'نتائج البحث عن الأدوية:',
            'data' => DrugResource::collection($results)
        ]);
}


//show detals for medicien
 public function show($id)
{
    try {
        $medicine = Medicine::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'تفاصيل الدواء.',
            'data' => new MedicineResource($medicine),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في جلب تفاصيل الدواء.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


 public function getByCategory($categoryId)
{
    try {
        $medicines = Medicine::where('category_id', $categoryId)->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب الأدوية حسب الصنف.',
            'data' => MedicineResource::collection($medicines),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في جلب الأدوية حسب الصنف.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function index()
{
    try {
        $medicines = Medicine::orderBy('name_en')->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب جميع الأدوية.',
            'data' => MedicineResource::collection($medicines),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في جلب الأدوية.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

// add a new medicien by admin only
public function store(Request $request)
{
    $validated = $request->validate([
        'name_en' => 'required|string|max:255',
        'name_ar' => 'required|string|max:255',
        'barcode' => 'required|string|max:255|unique:medicines,barcode',
        'category_id' => 'required|exists:categories,id',
        'image_url' => 'nullable|url', // ✅ تأكد من أنه رابط إنترنت صالح
        'manufacturer' => 'required|string|max:255',
        'pharmacy_price' => 'required|numeric|min:0',
        'consumer_price' => 'required|numeric|min:0',
        'discount' => 'nullable|numeric|min:0|max:100',
        'stock_quantity' => 'required|integer|min:0',
        'expiry_date' => 'required|date|after:today',
        'composition' => 'required|string',
        'needs_prescription' => 'boolean',
        'reorder_level' => 'nullable|integer|min:0',
        'admin_id' => 'nullable|exists:users,id',
    ]);

    $medicine = Medicine::create($validated);

    return response()->json([
        'message' => '✅ تم إضافة الدواء بنجاح',
        'data' => $medicine
    ], 201);
}

public function update(Request $request, $id)
{
    $medicine = Medicine::findOrFail($id);

    $validated = $request->validate([
        'name_en' => 'sometimes|string|max:255',
        'name_ar' => 'sometimes|string|max:255',
        'barcode' => 'sometimes|string|max:255|unique:medicines,barcode,' . $id,
        'category_id' => 'sometimes|exists:categories,id',
        'image_url' => 'nullable|url',
        'manufacturer' => 'sometimes|string|max:255',
        'pharmacy_price' => 'sometimes|numeric|min:0',
        'consumer_price' => 'sometimes|numeric|min:0',
        'discount' => 'nullable|numeric|min:0|max:100',
        'stock_quantity' => 'sometimes|integer|min:0',
        'expiry_date' => 'sometimes|date|after:today',
        'composition' => 'sometimes|string',
        'needs_prescription' => 'sometimes|boolean',
        'reorder_level' => 'sometimes|integer|min:0',
        'admin_id' => 'nullable|exists:users,id',
    ]);

    $medicine->update($validated);

    return response()->json([
        'message' => '✅ تم تحديث بيانات الدواء بنجاح',
        'data' => $medicine
    ]);
}
public function destroy($id)
{
    $medicine = Medicine::findOrFail($id);
    $medicine->delete();

    return response()->json([
        'message' => '🗑️ تم حذف الدواء بنجاح'
    ]);
}

//

 //قراءة الدواء من خلال الباركود
  public function scan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $medicine = Medicine::where('barcode', $request->barcode)->first();

        if (!$medicine) {
            return response()->json([
                'message' => 'الدواء غير موجود',
            ], 404);
        }

        return response()->json([
            'id' => $medicine->id,
            'name_en' => $medicine->name_en,
            'name_ar' => $medicine->name_ar,
            'category_id' => $medicine->category_id,
            'consumer_price' => $medicine->consumer_price,
            'expiry_date' => $medicine->expiry_date
        ]);
    }
}
