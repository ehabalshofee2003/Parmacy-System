<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\CartController;


Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/categories/{id}/medicines', [CategoryController::class, 'medicines']);
    Route::apiResource('categories', CategoryController::class)->only(['show','index']);
    Route::get('/drugs/search', [MedicineController::class, 'search']);
    Route::get('/supplies/search', [SupplyController::class, 'search']);
    Route::middleware('isAdmin')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::put('/admin/users/{id}', [UserController::class, 'update']);
        Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
        Route::apiResource('medicines', MedicineController::class);
        Route::apiResource('categories', CategoryController::class);

    });

Route::middleware(['auth:sanctum', 'role:pharmacist'])->group(function () {
        // راوتات خاصة بالموظف الصيدلي
        Route::apiResource('bills', BillController::class)->only(['store', 'index', 'show']);
        Route::apiResource('medicines', MedicineController::class)->only(['index','show']);
        Route::post('/cart', [CartController::class, 'store']); // add items to cart
        Route::put('/cart/item/{id}', [CartController::class, 'updateCartItem']); //التعديل على عناصر السلة
        Route::delete('/cart/item/{id}', [CartController::class, 'deleteCartItem']);//حذف عناصر م السلة
        Route::delete('/cart/{id}', [CartController::class, 'deleteCart']);//حذف السلة
        Route::delete('/cart/all', [CartController::class, 'deleteAllCartsForCurrentPharmacist']);//حذف جميع السلل الموجودة
        Route::post('/cart/{id}/confirm', [CartController::class, 'confirmCart']);//تاكيد السلة الى فاتوروة
        Route::get('/carts', [CartController::class, 'index']);//استعراض جميع السلل
        Route::get('/carts/{id}', [CartController::class, 'show']);//استعراض تفاصيل سلة معينة
        Route::post('/scan-barcode', [MedicineController::class, 'scan']);
        Route::get('supplies/category/{id}',[SupplyController::class , 'getByCategory']);//استعراض المستلزمات المرتبطة بصنف معين
        Route::get('supplies/{id}',[SupplyController::class , 'show']);//استعراض تفاصيل مستلزم معين
        Route::get('/bills/{id}', [BillController::class, 'show']);

    });
});


/*

// ✅ راوتات تسجيل الدخول والتسجيل
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/register', [AuthController::class, 'registerAdmin']);

// ✅ راوتات محمية بالتوكن
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // ✅ فقط الأدمن
    Route::middleware('role:admin')->group(function () {

        // إدارة المستخدمين
        Route::apiResource('users', AuthController::class);
        Route::patch('users/{id}/toggle-status', [AuthController::class, 'toggleStatus']);

        // إدارة الأدوية
        Route::apiResource('medicines', MedicineController::class);

        // إدارة كل الفواتير
        Route::apiResource('bills', BillController::class);

        // إدارة الورديات
        Route::get('shifts', [ShiftController::class, 'index']);
        Route::get('shifts/{id}/summary', [ShiftController::class, 'summary']);
    });

    // ✅ فقط الصيدلي
  Route::middleware(['auth:sanctum', 'pharmacist'])->group(function () {

        // الورديات الخاصة به
        Route::post('shifts/start', [ShiftController::class, 'start']);
        Route::post('shifts/end', [ShiftController::class, 'end']);
        Route::get('shifts/current', [ShiftController::class, 'current']);
        Route::get('my-shifts', [ShiftController::class, 'userShifts']);

        // إدارة فواتيره فقط
        Route::apiResource('bills', BillController::class)->only(['store', 'index', 'show']);
    });
});
*/

/*
|--------------------------------------------------------------------------
| Public Routes (unauthenticated)
|--------------------------------------------------------------------------
*/
/*Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Routes (authenticated users)
|--------------------------------------------------------------------------
*/
/*Route::middleware('auth:sanctum')->group(function () {

    // عام لكل المستخدمين المسجلين
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // راوتات خاصة بالأدمن فقط (مثل إنشاء موظفين)
    Route::middleware('isAdmin')->group(function () {
        // إدارة المستخدمين
        Route::apiResource('users', AuthController::class);
        Route::patch('users/{id}/toggle-status', [AuthController::class, 'toggleStatus']);

        // إدارة الأدوية
        Route::apiResource('medicines', MedicineController::class);

        // إدارة كل الفواتير
        Route::apiResource('bills', BillController::class);

        // إدارة الورديات
        Route::get('shifts', [ShiftController::class, 'index']);
        Route::get('shifts/{id}/summary', [ShiftController::class, 'summary']);
    });

    // راوتات الموظف العادي
    Route::middleware('isPharmacist')->group(function () {
         // الورديات الخاصة به
        Route::post('shifts/start', [ShiftController::class, 'start']);
        Route::post('shifts/end', [ShiftController::class, 'end']);
        Route::get('shifts/current', [ShiftController::class, 'current']);
        Route::get('my-shifts', [ShiftController::class, 'userShifts']);

        // إدارة فواتيره فقط
        Route::apiResource('bills', BillController::class)->only(['store', 'index', 'show']);
    });

});*/
    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
   /* Route::middleware('isAdmin')->prefix('admin')->name('admin.')->group(function () {

        // إدارة المستخدمين
        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

        // إدارة الأدوية
        Route::apiResource('medicines', MedicineController::class);

        // إدارة كل الفواتير
        Route::apiResource('bills', BillController::class);

        // إدارة الورديات
        Route::get('shifts', [ShiftController::class, 'index']);
        Route::get('shifts/{id}/summary', [ShiftController::class, 'summary']);
    });

    /*
    |--------------------------------------------------------------------------
    | Pharmacist Routes
    |--------------------------------------------------------------------------
    */
    /*Route::middleware('isPharmacist')->prefix('pharmacist')->name('pharmacist.')->group(function () {

        // الورديات الخاصة به
        Route::post('shifts/start', [ShiftController::class, 'start']);
        Route::post('shifts/end', [ShiftController::class, 'end']);
        Route::get('shifts/current', [ShiftController::class, 'current']);
        Route::get('my-shifts', [ShiftController::class, 'userShifts']);

        // إدارة فواتيره فقط
        Route::apiResource('bills', BillController::class)->only(['store', 'index', 'show']);
    });

});


*/
