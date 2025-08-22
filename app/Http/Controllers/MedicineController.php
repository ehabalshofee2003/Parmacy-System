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
    // نجيب القيم سواء من query string (GET) أو body (POST)
    $barcode     = $request->input('barcode');
    $nameEn      = $request->input('name_en');
    $nameAr      = $request->input('name_ar');
    $expiryDate  = $request->input('expiry_date');

    $query = Medicine::query()
        ->when($barcode, fn($q) => $q->where('barcode', $barcode))
        ->when($nameEn, fn($q) => $q->where('name_en', 'like', '%' . $nameEn . '%'))
        ->when($nameAr, fn($q) => $q->where('name_ar', 'like', '%' . $nameAr . '%'))
        ->when($expiryDate, fn($q) => $q->where('expiry_date', $expiryDate));

    $results = $query->get();

    if ($results->isEmpty()) {
        return response()->json([
            'status'  => false,
            'message' => 'No matching results found.',
            'data'    => []
        ], 404);
    }

    return response()->json([
        'status'  => true,
        'message' => 'Search results for medications:',
        'data'    => MedicineResource::collection($results)
    ]);
}


//show detals for medicien
 public function show($id)
{
    try {
        $medicine = Medicine::findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Details of the medicine.',
            'data' => new MedicineResource($medicine),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failure to provide medication details.',
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
            'message' => 'The medicines were brought in by category.',
            'data' => MedicineResource::collection($medicines),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failure to procure medicines by category.',
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
            'message' => ' The medication has been successfully added.',
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
        'image_url' => 'nullable|url|max:1000',
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

    if (empty($validated)) {
        return response()->json([
            'message' => 'No fields were sent for updating.',
            'data' => []
        ]);
    }

    $medicine->update($validated);

    return response()->json([
        'message' => 'Medication data successfully updated.',
        'data' => $medicine
    ]);
}

public function destroy($id)
{
    $medicine = Medicine::findOrFail($id);
    $medicine->delete();

    return response()->json([
        'message' => 'The medication has been successfully deleted.'
    ]);
}

// ✅ البحث بالـ POST (barcode من الـ body)
public function scanPost(Request $request)
{
    $request->validate([
        'barcode' => 'required|string',
    ]);

    $medicine = Medicine::where('barcode', $request->barcode)->first();

    if (!$medicine) {
        return response()->json([
            'status'  => false,
            'message' => 'The medicine is not available.',
        ], 404);
    }

    return response()->json([
        'status'  => true,
        'message' => 'The medicine has been found successfully.',
        'data'    => new MedicineResource($medicine),
    ]);
}


// ✅ البحث بالـ GET (barcode من الـ URL أو query string)
public function scanGet(Request $request)
{
    $request->validate([
        'barcode' => 'required|string',
    ]);

    $barcode = $request->query('barcode'); // أو $request->barcode لو مررته كـ /scan/{barcode}

    $medicine = Medicine::where('barcode', $barcode)->first();

    if (!$medicine) {
        return response()->json([
            'status'  => false,
            'message' => 'The medicine is not available.',
        ], 404);
    }

    return response()->json([
        'status'  => true,
        'message' => 'The medicine has been found successfully.',
        'data'    => new MedicineResource($medicine),
    ]);
}


}
