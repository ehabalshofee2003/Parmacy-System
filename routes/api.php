<?php
/**
 HI Raghed
 */
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StockReportcontroller;
use App\Http\Controllers\SalesReportcontroller;

use App\Http\Controllers\NotificationController;

Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
            //LOGOUT
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/profile', [AuthController::class, 'profile']);

            //SUPPLIES
            Route::get('/search-supply', [SupplyController::class, 'search']);
            Route::get('/supplies', [SupplyController::class, 'index']);          // عرض
            Route::get('/supplies/{id}', [SupplyController::class, 'show']);
            Route::get('/supplies/category/{categoryId}', [SupplyController::class, 'getByCategory']); // حسب الصنف
            //MEDICINES
            Route::get('/allMedicines', [MedicineController::class, 'index']);//استعراض جميع الادوية بدون تصنيف
            Route::get('/medicine', [MedicineController::class, 'index']); // كل الأدوية
            Route::get('/medicine/{id}', [MedicineController::class, 'show']); // دواء محدد
            Route::get('/medicine/category/{categoryId}', action: [MedicineController::class, 'getByCategory']); // حسب الصنف
            Route::get('/search-drug', [MedicineController::class, 'search']);

            //CATEGORY
            Route::get('/categories', [CategoryController::class, 'index']);
            Route::get('/categories/search', [CategoryController::class, 'search']);

            //BILLS
            Route::get('/bills/{billId}', [BillController::class, 'getConfirmedBillDetails']);
            Route::get('/bills', [BillController::class,'getConfirmedBills']);

            //الاشعارات
            Route::get('/notifications', [NotificationController::class, 'getNotifications']);
            Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

Route::middleware('isAdmin')->group(function () {
    Route::get('/test-image-url', [CategoryController::class, 'testImagePath']);
            Route::get('/admin/users', [UserController::class, 'index']);
            Route::post('/admin/users', [UserController::class, 'store']);
            Route::put('/admin/users/{id}', [UserController::class, 'update']);
            Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
            Route::post('/supplies', [SupplyController::class, 'store']);         // إضافة
            Route::put('/supplies/{id}', [SupplyController::class, 'update']);    // تعديل
            Route::delete('/supplies/{id}', [SupplyController::class, 'destroy']); // حذف
            Route::post('/medicines', [MedicineController::class, 'store']);
            Route::put('/medicines/{id}', [MedicineController::class, 'update']);   // تحديث
            Route::delete('/medicines/{id}', [MedicineController::class, 'destroy']); // حذف
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
            Route::apiResource('sales-reports', SalesReportController::class);
            Route::apiResource('stock-reports', StockReportController::class);
            Route::get('{id}/pdf', [SalesReportController::class, 'downloadPDF'])
            ->name('reports.sales.download');
            Route::get('reports/stock/{id}/pdf', [StockReportController::class, 'downloadPDF']);

            Route::get('/admin/bills', [BillController::class, 'getSentBills']);
            Route::get('/admin/bills/sent/{id}', [BillController::class, 'showSentBillDetails']);

    });

Route::middleware(['auth:sanctum', 'role:pharmacist'])->group(function () {
        // راوتات خاصة بالموظف الصيدلي
            Route::post('cart/create', [CartController::class, 'createNewCart']);
            Route::post('cart/add-item', [CartController::class, 'addItemToCart']);
            Route::get('cart/current', [CartController::class, 'getCurrentCart']);
            Route::post('cart/confirm', [CartController::class, 'confirmCart']);
            Route::put('/cart/update-name', [CartController::class, 'updateCartName']);
            Route::post('/cart/increase', [CartController::class, 'increaseQuantity']);
            Route::post('/cart/decrease', [CartController::class, 'decreaseQuantity']);
            Route::delete('/cart/remove-item', [CartController::class, 'removeCartItem']);
            Route::get('/carts/search', [CartController::class, 'searchCartsByCustomerName']);

            Route::put('/cart/item/{id}', [CartController::class, 'updateCartItem']); //التعديل على عناصر السلة
            Route::delete('/cart/item/{id}', [CartController::class, 'deleteCartItem']);//حذف عناصر م السلة
            Route::delete('/cart/{id}', [CartController::class, 'deleteCart']);//حذف السلة
            Route::delete('/carts/delete-all', [CartController::class, 'deleteAllCartsForCurrentPharmacist']);//حذف جميع السلل الموجودة
            Route::post('/carts/convert-to-bill', [CartController::class, 'convertCartToBill']);
            Route::post('/carts/confirm-all', [CartController::class, 'confirmAllCompletedCarts']);

            Route::get('/carts', [CartController::class, 'index']);//استعراض جميع السلل
            Route::get('/carts/{id}', [CartController::class, 'show']);//استعراض تفاصيل سلة معينة
            Route::post('/scan-barcode', [MedicineController::class, 'scan']);

            Route::post('/bills/send/{id}', [BillController::class, 'sendToAdmin']);
            Route::post('/bills/send-all', [BillController::class, 'sendAllBillsToAdmin']); // ارسال جميع الفواتير المؤكدة للادمن
             Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications', [NotificationController::class, 'store']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

    });
});
