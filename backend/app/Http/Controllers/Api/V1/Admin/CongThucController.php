<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CongThuc;
use App\Models\Mon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CongThucController extends Controller
{
    /**
     * Lấy công thức của một món ăn
     */
    public function show($maMon): JsonResponse
    {
        $mon = Mon::find($maMon);
        if (!$mon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $congThucs = CongThuc::where('ma_mon', $maMon)
            ->with('nguyenLieu:ma_nguyen_lieu,ten_nguyen_lieu,don_vi_tinh')
            ->get()
            ->map(function ($ct) {
                return [
                    'ma_mon' => $ct->ma_mon,
                    'ma_nguyen_lieu' => $ct->ma_nguyen_lieu,
                    'ten_nguyen_lieu' => $ct->nguyenLieu ? $ct->nguyenLieu->ten_nguyen_lieu : null,
                    'don_vi_tinh' => $ct->nguyenLieu ? $ct->nguyenLieu->don_vi_tinh : null,
                    'so_luong_can' => (float) $ct->so_luong_can,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'ma_mon' => $mon->ma_mon,
                'ten_mon' => $mon->ten_mon,
                'huong_dan' => $mon->cong_thuc,
                'nguyen_lieu' => $congThucs,
            ]
        ]);
    }

    /**
     * Lưu/cập nhật toàn bộ công thức cho một món
     * Body: { ma_mon: "...", nguyen_lieu: [{ ma_nguyen_lieu: "...", so_luong_can: ... }, ...] }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_mon' => 'required|string|exists:mons,ma_mon',
            'nguyen_lieu' => 'required|array',
            'nguyen_lieu.*.ma_nguyen_lieu' => 'required|string|exists:nguyen_lieus,ma_nguyen_lieu',
            'nguyen_lieu.*.so_luong_can' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $maMon = $request->input('ma_mon');

            // Xóa tất cả công thức cũ của món
            CongThuc::where('ma_mon', $maMon)->delete();

            // Thêm công thức mới
            foreach ($request->input('nguyen_lieu') as $nl) {
                CongThuc::create([
                    'ma_mon' => $maMon,
                    'ma_nguyen_lieu' => $nl['ma_nguyen_lieu'],
                    'so_luong_can' => $nl['so_luong_can'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật công thức thành công',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật công thức: ' . $e->getMessage()
            ], 500);
        }
    }
}
