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
            'data' => CategoryResource::collection($categories)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في جلب التصنيفات.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
public function testImagePath()
{
    return response()->json([
        'app_url' => config('app.url'),
'image_url' => url('storage/categories/UzuBfLUSN25rByYkQMChqUPkSrqKYDXpx08B20iU.jpg'),
    ]);
}
//add a new category by admin (not ready yet)
public function store(StoreCategoryRequest $request)
{
    try {
        DB::beginTransaction();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image_url' => 'nullable|url', // بدل image نتحقق انه رابط URL صالح
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'image_url' => $validated['image_url'] ?? null, // ناخد الرابط مباشرة
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة التصنيف بنجاح.',
            'data' => [
                ...$category->toArray(),
                // هنا ما في داعي تستخدم asset() لأنه الرابط مباشر من الإنترنت
                'image_url' => $category->image_url,
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
            'image_url' => 'nullable|url|max:1000', // صورة كرابط فقط
        ]);

        // تحديث الاسم إذا وُجد
        if ($request->filled('name')) {
            $category->name = $request->name;
        }

        // تحديث رابط الصورة إذا وُجد
        if ($request->filled('image_url')) {
            $category->image_url = $request->image_url;
        }

        $category->save();
        $category->refresh(); // إعادة تحميل البيانات من قاعدة البيانات بعد الحفظ

        return response()->json([
            'status' => true,
            'message' => 'تم التحديث بنجاح.',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'image_url' => $category->image_url,
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
