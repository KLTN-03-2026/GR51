<?php

use Illuminate\Support\Facades\Route;

Route::post('/v1/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);

    // Đơn hàng (POS Flow)
    Route::get('/v1/don-hang/kds', [\App\Http\Controllers\Api\V1\DonHangController::class, 'getKdsOrders']);
    Route::get('/v1/don-hang', [\App\Http\Controllers\Api\V1\DonHangController::class, 'index']);
    Route::post('/v1/don-hang', [\App\Http\Controllers\Api\V1\DonHangController::class, 'store']);
    Route::put('/v1/don-hang/{maDonHang}', [\App\Http\Controllers\Api\V1\DonHangController::class, 'update']);
    Route::put('/v1/don-hang/{maDonHang}/status', [\App\Http\Controllers\Api\V1\DonHangController::class, 'updateStatus']);
});

Route::get('/v1/menu', [\App\Http\Controllers\Api\V1\MenuController::class, 'index']);
Route::get('/v1/tables', [\App\Http\Controllers\Api\V1\TableController::class, 'index']);
Route::get('/v1/tables/{ma_ban}', [\App\Http\Controllers\Api\V1\TableController::class, 'show']); // Added for web order
Route::get('/v1/mon-an/{ma_mon}/cong-thuc', [\App\Http\Controllers\Api\V1\MonController::class, 'getCongThuc']);
Route::get('/v1/kho/ton-kho', [\App\Http\Controllers\Api\V1\KhoController::class, 'getTonKho']);

// Web Order Public Route
Route::post('/v1/don-hang/qr', [\App\Http\Controllers\Api\V1\DonHangController::class, 'storeQr']);
Route::get('/v1/don-hang/qr/{maDonHang}', [\App\Http\Controllers\Api\V1\DonHangController::class, 'showQr']);
Route::post('/v1/danh-gia/qr', [\App\Http\Controllers\Api\V1\DanhGiaController::class, 'storeQr']);

Route::get('/v1/ca-lam/hien-tai', [\App\Http\Controllers\Api\V1\CaLamController::class, 'getCurrentShift']);
Route::post('/v1/ca-lam/ket-ca', [\App\Http\Controllers\Api\V1\CaLamController::class, 'closeShift']);
Route::post('/v1/ca-lam/mo-ca', [\App\Http\Controllers\Api\V1\CaLamController::class, 'openShift']);

// ============================================================
// ADMIN ROUTES - Yêu cầu đăng nhập + vai trò quản lý
// ============================================================
Route::middleware(['auth:sanctum', 'admin'])->prefix('v1/admin')->group(function () {

    // Dashboard thống kê
    Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'index']);

    // Quản lý Danh mục
    Route::get('/danh-muc', [\App\Http\Controllers\Api\V1\Admin\DanhMucController::class, 'index']);
    Route::post('/danh-muc', [\App\Http\Controllers\Api\V1\Admin\DanhMucController::class, 'store']);
    Route::put('/danh-muc/{id}', [\App\Http\Controllers\Api\V1\Admin\DanhMucController::class, 'update']);
    Route::delete('/danh-muc/{id}', [\App\Http\Controllers\Api\V1\Admin\DanhMucController::class, 'destroy']);

    // Quản lý Món ăn
    Route::get('/mon', [\App\Http\Controllers\Api\V1\Admin\MonController::class, 'index']);
    Route::post('/mon', [\App\Http\Controllers\Api\V1\Admin\MonController::class, 'store']);
    Route::put('/mon/{id}', [\App\Http\Controllers\Api\V1\Admin\MonController::class, 'update']);
    Route::delete('/mon/{id}', [\App\Http\Controllers\Api\V1\Admin\MonController::class, 'destroy']);

    // Quản lý Kích cỡ
    Route::get('/kich-co', [\App\Http\Controllers\Api\V1\Admin\KichCoController::class, 'index']);
    Route::post('/kich-co', [\App\Http\Controllers\Api\V1\Admin\KichCoController::class, 'store']);
    Route::put('/kich-co/{id}', [\App\Http\Controllers\Api\V1\Admin\KichCoController::class, 'update']);
    Route::delete('/kich-co/{id}', [\App\Http\Controllers\Api\V1\Admin\KichCoController::class, 'destroy']);

    // Quản lý Topping
    Route::get('/topping', [\App\Http\Controllers\Api\V1\Admin\ToppingController::class, 'index']);
    Route::post('/topping', [\App\Http\Controllers\Api\V1\Admin\ToppingController::class, 'store']);
    Route::put('/topping/{id}', [\App\Http\Controllers\Api\V1\Admin\ToppingController::class, 'update']);
    Route::delete('/topping/{id}', [\App\Http\Controllers\Api\V1\Admin\ToppingController::class, 'destroy']);

    // Quản lý Công thức
    Route::get('/cong-thuc/{maMon}', [\App\Http\Controllers\Api\V1\Admin\CongThucController::class, 'show']);
    Route::post('/cong-thuc', [\App\Http\Controllers\Api\V1\Admin\CongThucController::class, 'store']);

    // Quản lý Nguyên liệu & Kho
    Route::get('/nguyen-lieu', [\App\Http\Controllers\Api\V1\Admin\NguyenLieuController::class, 'index']);
    Route::post('/nguyen-lieu', [\App\Http\Controllers\Api\V1\Admin\NguyenLieuController::class, 'store']);
    Route::put('/nguyen-lieu/{id}', [\App\Http\Controllers\Api\V1\Admin\NguyenLieuController::class, 'update']);
    Route::delete('/nguyen-lieu/{id}', [\App\Http\Controllers\Api\V1\Admin\NguyenLieuController::class, 'destroy']);
    Route::post('/nguyen-lieu/{id}/nhap-kho', [\App\Http\Controllers\Api\V1\Admin\NguyenLieuController::class, 'nhapKho']);
    Route::get('/lich-su-kho', [\App\Http\Controllers\Api\V1\Admin\NguyenLieuController::class, 'lichSuKho']);

    // Quản lý Đơn hàng
    Route::get('/don-hang', [\App\Http\Controllers\Api\V1\Admin\DonHangController::class, 'index']);

    // Quản lý Khu vực
    Route::get('/khu-vuc', [\App\Http\Controllers\Api\V1\Admin\KhuVucController::class, 'index']);
    Route::post('/khu-vuc', [\App\Http\Controllers\Api\V1\Admin\KhuVucController::class, 'store']);
    Route::put('/khu-vuc/{id}', [\App\Http\Controllers\Api\V1\Admin\KhuVucController::class, 'update']);
    Route::delete('/khu-vuc/{id}', [\App\Http\Controllers\Api\V1\Admin\KhuVucController::class, 'destroy']);

    // Quản lý Bàn
    Route::get('/ban', [\App\Http\Controllers\Api\V1\Admin\BanController::class, 'index']);
    Route::post('/ban', [\App\Http\Controllers\Api\V1\Admin\BanController::class, 'store']);
    Route::put('/ban/{id}', [\App\Http\Controllers\Api\V1\Admin\BanController::class, 'update']);
    Route::delete('/ban/{id}', [\App\Http\Controllers\Api\V1\Admin\BanController::class, 'destroy']);

    // Quản lý Nhân sự
    Route::get('/nhan-su', [\App\Http\Controllers\Api\V1\Admin\NhanSuController::class, 'index']);
    Route::post('/nhan-su', [\App\Http\Controllers\Api\V1\Admin\NhanSuController::class, 'store']);
    Route::put('/nhan-su/{id}', [\App\Http\Controllers\Api\V1\Admin\NhanSuController::class, 'update']);
    Route::delete('/nhan-su/{id}', [\App\Http\Controllers\Api\V1\Admin\NhanSuController::class, 'destroy']);
    Route::put('/nhan-su/{id}/reset-password', [\App\Http\Controllers\Api\V1\Admin\NhanSuController::class, 'resetPassword']);

    // Đánh giá khách hàng
    Route::get('/danh-gia', [\App\Http\Controllers\Api\V1\Admin\DanhGiaController::class, 'index']);

    // Lịch sử ca làm
    Route::get('/ca-lam', [\App\Http\Controllers\Api\V1\Admin\CaLamController::class, 'index']);

    // AI Trợ lý Quản lý
    Route::post('/ai-chat', [\App\Http\Controllers\Api\V1\Admin\AIController::class, 'chat']);
    Route::get('/ai-chat/history', [\App\Http\Controllers\Api\V1\Admin\AIController::class, 'history']);
    Route::delete('/ai-chat/history', [\App\Http\Controllers\Api\V1\Admin\AIController::class, 'clearHistory']);
});

