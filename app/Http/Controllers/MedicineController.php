<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMedicineRequest;
use App\Http\Requests\UpdateMedicineRequest;

use App\Http\Resources\MedicineResource;
use App\Models\category;
class MedicineController extends Controller
{
    /*
 ✅ عرض كل الأدوية

✅ عرض دواء محدد

✅ إنشاء دواء

✅ تعديل دواء

✅ حذف دواء
*/
public function index(Request $request)
{
    $query = Medicine::query();

    // 🔍 فلترة بالاسم
    if ($request->has('search') && $request->search !== null) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name_en', 'like', "%$search%")
              ->orWhere('name_ar', 'like', "%$search%");
        });
    }

    // 💵 فلترة حسب السعر الأعلى
    if ($request->has('max_price') && is_numeric($request->max_price)) {
        $query->where('consumer_price', '<=', $request->max_price);
    }

    // ⏳ فلترة حسب تاريخ الانتهاء
    if ($request->has('expiry_before')) {
        $query->where('expiry_date', '<=', $request->expiry_before);
    }

    if ($request->has('expiry_after')) {
        $query->where('expiry_date', '>=', $request->expiry_after);
    }

    // 💊 فلترة حسب الحاجة لوصفة
    if ($request->has('needs_prescription')) {
        $query->where('needs_prescription', $request->needs_prescription);
    }

    // 🔃 الترتيب
    $sortFields = ['name_en', 'name_ar', 'consumer_price', 'expiry_date'];
    $sortBy = in_array($request->get('sort_by'), $sortFields) ? $request->get('sort_by') : 'id';
    $sortOrder = $request->get('sort_order') === 'desc' ? 'desc' : 'asc';

    $query->orderBy($sortBy, $sortOrder);

    // 📄 النتائج مع ترقيم الصفحات
    return response()->json([$query->paginate(10),      'status' => 200,]);
}
/*
🧪 أمثلة على طلبات Postman
نوع الفلترة	رابط الـ API
الاسم يحتوي على "panadol"	/api/medicines?search=panadol
السعر أقل من أو يساوي 50	/api/medicines?max_price=50
انتهاء الصلاحية قبل 2025-12-01	/api/medicines?expiry_before=2025-12-01
انتهاء الصلاحية بعد 2025-06-01	/api/medicines?expiry_after=2025-06-01
يحتاج وصفة فقط	/api/medicines?needs_prescription=1
دمج بين الكل	/api/medicines?search=para&max_price=30&expiry_before=2026-01-01&needs_prescription=1

🧪 أمثلة على روابط Postman للترتيب:
الترتيب المطلوب	رابط الـ API
حسب السعر تصاعديًا	/api/medicines?sort_by=consumer_price&sort_order=asc
حسب السعر تنازليًا	/api/medicines?sort_by=consumer_price&sort_order=desc
حسب تاريخ الانتهاء	/api/medicines?sort_by=expiry_date&sort_order=asc
حسب الاسم العربي تنازليًا	/api/medicines?sort_by=name_ar&sort_order=desc
*/
 public function show($id)
{
    $medicine = Medicine::find($id);

    if (!$medicine) {
        return response()->json(['message' => 'الدواء غير موجود' , 'status' => 404], 404);
    }

    return response()->json([
        'status' => 200,
        'data' => new MedicineResource($medicine)
    ], 200);
}
public function store(StoreMedicineRequest  $request)
    {
        $validated = $request->validated();
        $medicine = Medicine::create($validated);
         return (new MedicineResource($medicine))
        ->additional(['message' => 'تمت الإضافة بنجاح.',
                'status' => 201,
])
        ->response()
        ->setStatusCode(201);
}
 public function update(UpdateMedicineRequest  $request, $id)
    {
        $medicine = Medicine::find($id);

        if (!$medicine) {
            return response()->json(['message' => 'الدواء غير موجود' , 'status' => 404], 404);
        }

        $validated = $request->validated();
        $medicine->update($validated);

 return (new MedicineResource($medicine))
    ->additional([
        'message' => 'تم التحديث بنجاح.',
                'status' => 200,

    ])
    ->response()
    ->setStatusCode(200);

}
public function destroy($id)
    {
        $medicine = Medicine::find($id);

        if (!$medicine) {
            return response()->json(['message' => 'الدواء غير موجود' , 'status' => 404], 404);
        }

        $medicine->delete();

        return response()->json(['message' => 'تم حذف الدواء بنجاح', 'status' => 204,] , 204);
 }
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
