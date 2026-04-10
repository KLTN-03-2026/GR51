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
Route::get('/v1/mon-an/{ma_mon}/cong-thuc', [\App\Http\Controllers\Api\V1\MonController::class, 'getCongThuc']);
Route::get('/v1/kho/ton-kho', [\App\Http\Controllers\Api\V1\KhoController::class, 'getTonKho']);



Route::get('/v1/ca-lam/hien-tai', [\App\Http\Controllers\Api\V1\CaLamController::class, 'getCurrentShift']);
Route::post('/v1/ca-lam/ket-ca', [\App\Http\Controllers\Api\V1\CaLamController::class, 'closeShift']);

Route::post('/v1/ca-lam/mo-ca', [\App\Http\Controllers\Api\V1\CaLamController::class, 'openShift']);
