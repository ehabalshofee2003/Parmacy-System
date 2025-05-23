<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\AdminRegisterRequest;

class AuthController extends Controller
{
  // تسجيل حساب أدمن
public function register(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'username' => 'required|unique:users',
        'phone' => 'required',
        'password' => 'required|confirmed',
    ]);

    $validated['password'] = bcrypt($validated['password']);
    $validated['role'] = 'admin'; // فقط الأدمن يستطيع التسجيل

    $user = User::create($validated);

    $token = $user->createToken('admin_token')->plainTextToken;

    return response()->json(['token' => $token , 'status' => 201]);
}

public function login(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::where('username', $request->username)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'اسم المستخدم او كلمة المرور غير صحيحة '], 401);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json(['token' => $token, 'role' => $user->role , 'status' => 200] );
}

public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Logged out'] , 200);
}

public function profile(Request $request)
{
    return response()->json($request->user());
}



}
