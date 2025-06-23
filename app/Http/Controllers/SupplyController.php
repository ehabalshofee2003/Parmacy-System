<?php

namespace App\Http\Controllers;

use App\Models\supply;
use App\Http\Resources\SupplyResource;
use App\Http\Requests\SearchSupplyRequest;
use Illuminate\Http\Request;

class SupplyController extends Controller
{
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

}
