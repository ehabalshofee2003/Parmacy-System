<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;

class UserController extends Controller
{
public function index()
{
    return User::where('role', 'pharmacist')
                ->get(['username', 'password']) ;
}
public function store(Request $request)
{
    $validated = $request->validate([
        'username' => 'required|unique:users',
        'password' => 'required',
    ]);

    $validated['password'] = bcrypt($validated['password']);
    $validated['role'] = 'pharmacist';

    $user = User::create($validated);

    return response()->json([$user, 'status' => 201], 201);
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    // منع تعديل حساب الأدمن
    if ($user->role === 'admin') {
        return response()->json(['message' => 'لا يمكن تعديل حساب الأدمن.'], 403);
    }

    // تحقق من البيانات المدخلة
    $validated = $request->validate([
        'username' => 'sometimes|string|unique:users,username,' . $id,
        'password' => 'sometimes|string|min:6',
    ]);

    // تحديث الحقول المرسلة فقط
    if (isset($validated['username'])) {
        $user->username = $validated['username'];
    }

    if (isset($validated['password'])) {
        $user->password = bcrypt($validated['password']);
    }

    $user->save();

    return response()->json(['message' => 'تم تحديث بيانات الموظف بنجاح.', 'user' => $user->username , 'status' => 200 ],200);
}


public function destroy($id)
{
    $user = User::find($id);
if (!$user) {
    return response()->json(['message' => 'المستخدم غير موجود'], 404);
}
    $user->delete();

return response()->json(['message' => 'تم حذف المستخدم بنجاح'], 200);
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
