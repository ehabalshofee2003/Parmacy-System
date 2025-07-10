<?php

namespace App\Http\Controllers;

use App\Models\category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;

class CategoryController extends Controller
{

    //return all categories
public function index()
{
    try {
        $categories = Category::all();

        return response()->json([
            'status' => 200,
            'message' => 'تم جلب التصنيفات بنجاح.',
            'data' => CategoryResource::collection($categories),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في جلب التصنيفات.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
//add a new category by admin (not ready yet)
public function store(StoreCategoryRequest $request)
{
    try {
        DB::beginTransaction();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // تحقق من الصورة
        ]);

        // معالجة رفع الصورة
        if ($request->hasFile('image_url')) {
            $path = $request->file('image_url')->store('categories', 'public');
            $validated['image_url'] = $path; // تخزين المسار النسبي: categories/xxx.jpg
        }

        $category = Category::create([
            'name' => $validated['name'],
            'image_url' => $validated['image_url'] ?? null, // السماح بقيمة null إذا لم يتم رفع صورة
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة التصنيف بنجاح.',
            'data' => [
                ...$category->toArray(),
                'image_url' => $category->image_url ? asset('storage/' . $category->image_url) : null, // إرجاع المسار الكامل
            ],
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status' => false,
            'message' => 'فشل في إضافة التصنيف.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


public function show($id)
{
    $category = Category::findOrFail($id);
    return response()->json([$category, 'status' => 200,]);
}
//update name or image to category (not ready yet)

public function update(Request $request, $id)
{
    try {
        $category = Category::findOrFail($id);

        // التحقق من البيانات
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'image_url' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:2048',
        ]);
         // تحديث الاسم إذا وُجد
        if ($request->filled('name')) {
            $category->name = $request->name;
        }

        // تحديث الصورة إذا تم رفعها
        if ($request->hasFile('image_url')) {
            // حذف الصورة القديمة إن وُجدت وكانت محلية
            if ($category->image_url && \Storage::disk('public')->exists($category->image_url)) {
                \Storage::disk('public')->delete($category->image_url);
            }

            // حفظ الصورة الجديدة
            $category->image_url = $request->file('image_url')->store('categories', 'public');
        }

        $category->save();
        $category->refresh(); // إعادة تحميل البيانات من قاعدة البيانات بعد الحفظ

        return response()->json([
            'status' => true,
            'message' => 'تم التحديث بنجاح.',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $category->image_url ? asset('storage/' . $category->image_url) : null,
            ],
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'خطأ في التحقق.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'حدث خطأ أثناء التحديث.',
            'error' => $e->getMessage(),
        ], 500);
    }
}




//delete category
public function destroy($id)
{
    try {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف التصنيف بنجاح.',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في حذف التصنيف.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
