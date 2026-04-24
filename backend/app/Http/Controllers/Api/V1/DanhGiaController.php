<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DanhGiaController extends Controller
{
    public function storeQr(Request $request): JsonResponse
    {
        $request->validate([
            'ma_don_hang' => 'required|string',
            'so_sao' => 'required|integer|min:1|max:5',
            'binh_luan' => 'nullable|string|max:1000'
        ]);

        $maDonHang = $request->input('ma_don_hang');

        // Validate order exists
        $donHang = DonHang::find($maDonHang);
        if (!$donHang) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng'
            ], 404);
        }

        // Check if already reviewed
        $existing = DanhGia::where('ma_don_hang', $maDonHang)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn hàng này đã được đánh giá rồi!'
            ], 400);
        }

        $maDanhGia = 'DG_' . time() . '_' . rand(100, 999);

        try {
            $danhGia = DanhGia::create([
                'ma_danh_gia' => $maDanhGia,
                'ma_don_hang' => $maDonHang,
                'so_sao' => $request->input('so_sao'),
                'binh_luan' => $request->input('binh_luan'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi đánh giá thành công',
                'data' => $danhGia
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu đánh giá: ' . $e->getMessage()
            ], 500);
        }
    }
}
