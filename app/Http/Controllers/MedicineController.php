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

//  البحث عن دواء من خلال الاسم  / رقم الباركود / تاريخ الصلاحية /الكمية
public function search(Request $request)
{
    $query = Medicine::query()
        ->when($request->query('barcode'), fn($q) => $q->where('barcode', $request->query('barcode')))
        ->when($request->query('name_en'), fn($q) => $q->where('name_en', 'like', '%' . $request->query('name_en') . '%'))
        ->when($request->query('name_ar'), fn($q) => $q->where('name_ar', 'like', '%' . $request->query('name_ar') . '%'))
        ->when($request->query('expiry_date'), fn($q) => $q->where('expiry_date', $request->query('expiry_date')));

    $results = $query->get();

    if ($results->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'لا توجد نتائج مطابقة.',
            'data' => []
        ]);
    }

    return response()->json([
        'status' => 200,
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
      $medicines = Medicine::orderBy('id')->get();

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
        'category_id' => 'exists:categories,id',
        'image_url' => 'nullable|url', // رابط صورة من الإنترنت
        'manufacturer' => 'nullable|string|max:255',
        'pharmacy_price' => 'required|numeric|min:0',
        'consumer_price' => 'required|numeric|min:0',
        'discount' => 'nullable|numeric|min:0|max:100',
        'stock_quantity' => 'required|integer|min:0',
        'expiry_date' => 'required|date|after:today',
        'composition' => 'nullable|string',
        'needs_prescription' => 'boolean',
        'reorder_level' => 'nullable|integer|min:0',
        'admin_id' => 'nullable|exists:users,id',
    ]);

    try {
        // ما في رفع صورة، فقط يتم الحفظ المباشر
        $medicine = Medicine::create($validated);

        return response()->json([
            'message' => '✅ تم إضافة الدواء بنجاح',
            'data' => $medicine,
        ], 201);
    } catch (\Exception $e) {
        \Log::error('Error storing medicine: ' . $e->getMessage());
        return response()->json([
            'message' => 'حدث خطأ أثناء إضافة الدواء',
            'error' => $e->getMessage(),
        ], 500);
    }
}




public function update(Request $request, $id)
{
    $medicine = Medicine::findOrFail($id);

    $validated = $request->validate([
        'name_en' => 'sometimes|string|max:255',
        'name_ar' => 'sometimes|string|max:255',
        'barcode' => 'sometimes|string|max:255|unique:medicines,barcode,' . $id,
        'category_id' => 'sometimes|exists:categories,id',
        'image_url' => 'nullable|url|max:1000', // رابط صورة فقط
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

    // لا داعي لرفع ملفات بعد الآن
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
