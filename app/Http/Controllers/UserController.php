<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
public function index()
{
    $pharmacists = User::where('role', 'pharmacist')
        ->select('id', 'username')
        ->get();

    return response()->json([
        'status' => 200,
        'employees' => $pharmacists
    ]);
}

public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => 'required|unique:users',
            'phone' => 'required|string',
            'password' => 'required',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['role'] = 'pharmacist';

        $user = User::create($validated);

        return response()->json([
            'user' => $user,
            'status' => 201
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation Failed',
            'errors' => $e->errors(),
            'status' => 422
        ], 422); // أو 400 إذا أردت
    }
}
public function update(Request $request, $id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'message' => 'لا يوجد حساب بهذا الرقم.',
            'status' => 404
        ], 404);
    }

    // منع تعديل حساب الأدمن
    if ($user->role === 'admin') {
        return response()->json([
            'message' => 'لا يمكن تعديل حساب الأدمن.',
            'status' => 403
        ], 403);
    }

    // التحقق من البيانات المدخلة
    $validated = $request->validate([
        'first_name' => 'sometimes|string',
        'last_name' => 'sometimes|string',
        'username' => 'sometimes|string|unique:users,username,' . $id,
        'phone' => 'sometimes|string',
        'password' => 'sometimes|string|min:6',
    ]);

    // تحديث القيم المدخلة فقط
    $user->fill(collect($validated)->except('password')->toArray());

    if (isset($validated['password'])) {
        $user->password = bcrypt($validated['password']);
    }

    $user->save();

    return response()->json([
        'message' => 'تم تحديث بيانات الموظف بنجاح.',
        'user' => $user->only(['first_name', 'last_name', 'username', 'phone']),
        'status' => 200
    ], 200);
}


public function destroy($id)
{
    $user = User::find($id);
if (!$user) {
    return response()->json(['message' => 'المستخدم غير موجود' , 'status' => 404], 404);
}
    $user->delete();

return response()->json(['message' => 'تم حذف المستخدم بنجاح' , 'status' => 200], 200);
}

/*
git init                 # تهيئة مستودع جديد لو ما كان موجود
git add .                # إضافة كل الملفات للمستودع
git commit -m "Initial commit"  # أول تسجيل للتعديلات
===================
git checkout main          # الرجوع للفرع الرئيسي
git pull origin main       # جلب آخر تحديثات من المستودع البعيد
git checkout -b feature/اسم_الميزة  # إنشاء فرع جديد
git add .                 # إضافة كل الملفات المعدلة
git commit -m "وصف التعديل"  # تسجيل التعديل مع رسالة
git push origin feature/اسم_الميزة # رفع الفرع للمستودع البعيد
*/



}


