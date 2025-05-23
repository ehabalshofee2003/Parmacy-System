<?php

namespace App\Http\Controllers;

use App\Models\category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
  public function index()
{
    return response()->json([
        'status' => 200,
        'categories' => Category::all()
    ], 200);
}


public function store(Request $request)
{
    $request->validate(['name' => 'required|string|unique:categories,name']);
    $category = Category::create(['name' => $request->name]);
 return response()->json([
        'data' => $category,
        'status' => 201,
     ], 201);}
public function medicines($id)
{
    $category = Category::with('medicines')->findOrFail($id);
    return response()->json(['medicines ' => $category->medicines , 'status' => 200]);
}
public function show($id)
{
    $category = Category::findOrFail($id);
    return response()->json([$category, 'status' => 200,]);
}

public function update(Request $request, $id)
{
    $category = Category::findOrFail($id);
    $request->validate(['name' => 'required|string|unique:categories,name,' . $id]);
    $category->update(['name' => $request->name]);
    return response()->json([$category,'status' => 200]);
}

public function destroy($id)
{
    Category::destroy($id);
    return response()->json(['message' => 'Deleted' , 'status' => 204]);
}

}
