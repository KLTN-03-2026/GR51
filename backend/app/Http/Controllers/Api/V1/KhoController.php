<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NguyenLieu;

class KhoController extends Controller
{
    /**
     * API danh sách nguyên liệu cho màn hình Tồn kho
     * Ưu tiên hiển thị:
     * 1. Hết hàng (ton_kho <= 0)
     * 2. Sắp hết (ton_kho <= muc_canh_bao)
     * 3. Sắp xếp theo tên A-Z
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTonKho()
    {
        $nguyenLieus = NguyenLieu::select('id', 'ma_nguyen_lieu', 'ten_nguyen_lieu', 'don_vi_tinh', 'ton_kho', 'muc_canh_bao')
            ->where('trang_thai', 1)
            ->orderByRaw("
                CASE 
                    WHEN ton_kho <= 0 THEN 1
                    WHEN ton_kho <= muc_canh_bao THEN 2
                    ELSE 3
                END ASC
            ")
            ->orderBy('ten_nguyen_lieu', 'asc')
            ->get()
            ->map(function ($item) {
                // Ép kiểu float theo yêu cầu
                $item->ton_kho = (float) $item->ton_kho;
                $item->muc_canh_bao = (float) $item->muc_canh_bao;
                return $item;
            });

        return response()->json([
            'data' => $nguyenLieus,
            'message' => 'Lấy danh sách tồn kho thành công'
        ], 200);
    }
}
