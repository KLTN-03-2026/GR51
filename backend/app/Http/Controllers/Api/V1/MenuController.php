<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use App\Models\KichCo;
use App\Models\Topping;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        // 1. Lấy danh mục kèm món và eager load nguyên liệu để check tồn kho
        $danhMucs = DanhMuc::with(['mons.congThucs.nguyenLieu'])->get();

        // Xử lý logic check hết hàng
        $danhMucs->each(function ($danhMuc) {
            $danhMuc->mons->each(function ($mon) {
                $isHetHang = false;
                if ($mon->congThucs) {
                    foreach ($mon->congThucs as $congThuc) {
                        if ($congThuc->nguyenLieu && $congThuc->nguyenLieu->ton_kho < $congThuc->so_luong_can) {
                            $isHetHang = true;
                            break;
                        }
                    }
                }
                $mon->is_het_hang = $isHetHang;
            });
        });

        // 2. Lấy thêm danh sách Size và Topping
        $sizes = KichCo::all();
        $toppings = Topping::all();

        // 3. Gom tất cả vào chung mảng 'data'
        return response()->json([
            'success' => true,
            'message' => 'Lấy thực đơn kèm tùy chọn thành công',
            'data' => [
                'danh_mucs' => $danhMucs,
                'sizes' => $sizes,
                'toppings' => $toppings
            ]
        ]);
    }
}
