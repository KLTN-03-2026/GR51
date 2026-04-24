<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonController extends Controller
{
    /**
     * Danh sách món ăn (có filter + phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mon::with('danhMuc:ma_danh_muc,ten_danh_muc');

        // Filter theo danh mục
        if ($request->has('ma_danh_muc') && $request->ma_danh_muc) {
            $query->where('ma_danh_muc', $request->ma_danh_muc);
        }

        // Filter theo trạng thái
        if ($request->has('trang_thai') && $request->trang_thai) {
            $query->where('trang_thai', $request->trang_thai);
        }

        // Tìm kiếm theo tên
        if ($request->has('search') && $request->search) {
            $query->where('ten_mon', 'LIKE', '%' . $request->search . '%');
        }

        $mons = $query->orderBy('ten_mon')->get()->map(function ($mon) {
            return [
                'ma_mon' => $mon->ma_mon,
                'ma_danh_muc' => $mon->ma_danh_muc,
                'ten_danh_muc' => $mon->danhMuc ? $mon->danhMuc->ten_danh_muc : null,
                'ten_mon' => $mon->ten_mon,
                'hinh_anh' => $mon->hinh_anh,
                'gia_ban' => (float) $mon->gia_ban,
                'cong_thuc' => $mon->cong_thuc,
                'trang_thai' => $mon->trang_thai,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mons
        ]);
    }

    /**
     * Thêm món mới
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_mon' => 'required|string|unique:mons,ma_mon',
            'ma_danh_muc' => 'required|string|exists:danh_mucs,ma_danh_muc',
            'ten_mon' => 'required|string|max:255',
            'gia_ban' => 'required|numeric|min:0',
            'trang_thai' => 'required|string',
            'hinh_anh' => 'nullable|string',
            'cong_thuc' => 'nullable|string',
        ]);

        $mon = Mon::create($request->only([
            'ma_mon', 'ma_danh_muc', 'ten_mon', 'hinh_anh', 'gia_ban', 'cong_thuc', 'trang_thai'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Thêm món ăn thành công',
            'data' => $mon
        ], 201);
    }

    /**
     * Cập nhật món
     */
    public function update(Request $request, $maMon): JsonResponse
    {
        $mon = Mon::find($maMon);
        if (!$mon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $request->validate([
            'ma_danh_muc' => 'sometimes|required|string|exists:danh_mucs,ma_danh_muc',
            'ten_mon' => 'sometimes|required|string|max:255',
            'gia_ban' => 'sometimes|required|numeric|min:0',
            'trang_thai' => 'sometimes|required|string',
            'hinh_anh' => 'nullable|string',
            'cong_thuc' => 'nullable|string',
        ]);

        $mon->update($request->only([
            'ma_danh_muc', 'ten_mon', 'hinh_anh', 'gia_ban', 'cong_thuc', 'trang_thai'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật món ăn thành công',
            'data' => $mon
        ]);
    }

    /**
     * Xóa món
     */
    public function destroy($maMon): JsonResponse
    {
        $mon = Mon::find($maMon);
        if (!$mon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        // Kiểm tra có đơn hàng nào chứa món này không
        if ($mon->chiTietDonHangs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa món đang có trong đơn hàng. Hãy chuyển trạng thái sang ngừng bán.'
            ], 400);
        }

        // Xóa công thức liên quan
        $mon->congThucs()->delete();
        $mon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa món ăn thành công'
        ]);
    }
}
