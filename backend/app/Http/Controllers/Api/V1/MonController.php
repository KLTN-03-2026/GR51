<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mon;
use Illuminate\Http\JsonResponse;

class MonController extends Controller
{
    /**
     * Lấy chi tiết công thức của một món ăn
     * 
     * @param string $ma_mon
     * @return JsonResponse
     */
    public function getCongThuc(string $ma_mon): JsonResponse
    {
        // 1. Tìm món ăn và eager load công thức + nguyên liệu (thỏa mãn yêu cầu số 3)
        $mon = is_numeric($ma_mon) 
            ? Mon::with('congThucs.nguyenLieu')->find($ma_mon)
            : Mon::with('congThucs.nguyenLieu')->where('ma_mon', $ma_mon)->first();

        // Kiểm tra món ăn
        if (!$mon) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không tìm thấy món ăn'
            ], 404);
        }

        // Xử lý map danh sách định lượng (thỏa mãn yêu cầu số 2)
        $danhSachNguyenLieu = $mon->congThucs->map(function ($congThuc) {
            $nguyenLieu = $congThuc->nguyenLieu;
            
            return [
                'ten_nguyen_lieu' => $nguyenLieu ? $nguyenLieu->ten_nguyen_lieu : null,
                'so_luong_can' => (float)$congThuc->so_luong_can,
                'don_vi_tinh' => $nguyenLieu ? $nguyenLieu->don_vi_tinh : null
            ];
        });

        // Trả về JSON theo đúng định dạng mẫu yêu cầu (thỏa mãn yêu cầu số 1 và 4)
        return response()->json([
            'status' => 'success',
            'data' => [
                'huong_dan' => $mon->cong_thuc,
                'danh_sach_nguyen_lieu' => $danhSachNguyenLieu->values()->all()
            ]
        ], 200);
    }
}
