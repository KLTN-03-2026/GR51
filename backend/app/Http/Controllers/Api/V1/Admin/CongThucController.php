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
    public function show($id): JsonResponse
    {
        $mon = Mon::find($id);
        if (!$mon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $congThucs = CongThuc::where('mon_id', $id)
            ->with('nguyenLieu')
            ->get()
            ->map(function ($ct) {
                return [
                    'mon_id' => $ct->mon_id,
                    'nguyen_lieu_id' => $ct->nguyen_lieu_id,
                    'ten_nguyen_lieu' => $ct->nguyenLieu ? $ct->nguyenLieu->ten_nguyen_lieu : null,
                    'don_vi_tinh' => $ct->nguyenLieu ? $ct->nguyenLieu->don_vi_tinh : null,
                    'so_luong_can' => (float) $ct->so_luong_can,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $mon->id,
                'ma_mon' => $mon->ma_mon,
                'ten_mon' => $mon->ten_mon,
                'huong_dan' => $mon->cong_thuc,
                'nguyen_lieu' => $congThucs,
            ]
        ]);
    }

    /**
     * Lưu/cập nhật toàn bộ công thức cho một món
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'mon_id' => 'required|integer|exists:mons,id',
            'nguyen_lieu' => 'required|array',
            'nguyen_lieu.*.nguyen_lieu_id' => 'required|integer|exists:nguyen_lieus,id',
            'nguyen_lieu.*.so_luong_can' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();
            $monId = $request->input('mon_id');

            // Xóa tất cả công thức cũ của món
            CongThuc::where('mon_id', $monId)->delete();

            // Thêm công thức mới
            foreach ($request->input('nguyen_lieu') as $nl) {
                CongThuc::create([
                    'mon_id' => $monId,
                    'nguyen_lieu_id' => $nl['nguyen_lieu_id'],
                    'so_luong_can' => $nl['so_luong_can'],
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cập nhật công thức thành công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
