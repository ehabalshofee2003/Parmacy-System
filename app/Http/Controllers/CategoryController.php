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
            'status' => true,
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
       
        $category = Category::create([
            'name' => $request->name,
            'image_url' => $request->image_url, // ممكن يكون null أو رابط من الإنترنت
        ]);

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'تم إضافة التصنيف بنجاح.',
            'data' => $category,
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

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'image_url' => 'nullable|url', // الصورة ستكون من الإنترنت
        ]);

        $category->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'تم تعديل التصنيف بنجاح.',
            'data' => new CategoryResource($category),
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => false,
            'message' => 'خطأ في التحقق من البيانات.',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'فشل في تعديل التصنيف.',
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
