<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\AdminRegisterRequest;
use Illuminate\Validation\ValidationException;
use App\Services\BillService;
use App\Services\CartService;

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
                'fcm_token' => 'nullable|string',
            ]);

            $validated['password'] = bcrypt($validated['password']);
            $validated['role'] = 'admin';

            $user = User::create($validated);

            $token = $user->createToken('admin_token')->plainTextToken;

            return response()->json(['token' => $token, 'status' => 201], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $e->errors(),
                'status' => 422
            ], 422); // أو غيرها إن أردت مثل 400
        }
    }

//login for admin||pharmacist
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Username or password is incorrect.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'status' => 200,
            'message' => "You have been Logged in Successfully"
        ]);
    }

//logout for admin||pharmacist
    public function logout(Request $request)
    {
        $user = $request->user();

        // تنفيذ العمليات المؤجلة
        CartService::confirmAllPendingCarts($user);
        BillService::sendAllPendingBills($user);

        $user->update(['fcm_token' => null]);

        // حذف التوكن
        $user->tokens()->delete();

        return response()->json([
            'status' => 200,
            'message' => 'You have logged out and completed all operations',
        ]);
    }

//not ready yet
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

}
