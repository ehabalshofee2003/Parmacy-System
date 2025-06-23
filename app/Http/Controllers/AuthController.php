<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\AdminRegisterRequest;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //register for only admin
public function register(Request $request)
{
    try {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => 'required|unique:users',
            'phone' => 'required',
            'password' => 'required|confirmed',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['role'] = 'admin';

        $user = User::create($validated);

        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json(['token' => $token, 'status' => 201], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'Validation Failed',
            'errors' => $e->errors() ,
            'status' => 422
        ], 422); // أو غيرها إن أردت مثل 400
    }
}

//login for admin||pharmacist
public function login(Request $request)
{
    //هون عم نطلب من المستخدم يجيب اسم المستخدم وكلمة السر يلي الو
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);
    //هون عم ندور بقاعدة البيانات من خلال الاستعلامات اذا البيانات يلي كتبها المستخدم صح او لا
    $user = User::where('username', $request->username)->first();
    //هون اذا لقينا اسم المستخدم غلط او كلمة السر خطا منرجع برسالة انو البيانات المعتمدة غير صحيحىة
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'اسم المستخدم او كلمة المرور غير صحيحة ' , 'status' => 401], 401);
    }
    // اذا الامور تمام منولد توكن ( رمز دخول ) باستخدام LARAVEL SANCTUM وهاد الرمز بيستخدم بالتطبيق لحتى يضل مسجل دخول
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json(['token' => $token, 'role' => $user->role , 'status' => 200] );
}
//logout for admin||pharmacist
public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Logged out' , 'status' => 200] , 200);
}
//not ready yet 
public function profile(Request $request)
{
    return response()->json($request->user());
}

}
