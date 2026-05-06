<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mon;
use App\Models\DanhMuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonController extends Controller
{
    /**
     * Danh sách món ăn
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mon::with('danhMuc');

        // Filter theo danh mục (Sử dụng ID)
        if ($request->has('danh_muc_id') && $request->danh_muc_id) {
            $query->where('danh_muc_id', $request->danh_muc_id);
        }

        // Filter theo trạng thái (Số)
        if ($request->has('trang_thai') && $request->trang_thai !== null) {
            $query->where('trang_thai', $request->trang_thai);
        }

        // Tìm kiếm theo tên hoặc mã
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('ten_mon', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('ma_mon', 'LIKE', '%' . $request->search . '%');
            });
        }

        $mons = $query->orderBy('ten_mon')->get()->map(function ($mon) {
            return [
                'id' => $mon->id,
                'ma_mon' => $mon->ma_mon,
                'danh_muc_id' => $mon->danh_muc_id,
                'ten_danh_muc' => $mon->danhMuc ? $mon->danhMuc->ten_danh_muc : null,
                'ten_mon' => $mon->ten_mon,
                'hinh_anh' => $mon->hinh_anh,
                'gia_ban' => (float) $mon->gia_ban,
                'cong_thuc' => $mon->cong_thuc,
                'trang_thai' => (int)$mon->trang_thai,
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
            'danh_muc_id' => 'required|integer|exists:danh_mucs,id',
            'ten_mon' => 'required|string|max:255',
            'gia_ban' => 'required|numeric|min:0',
            'trang_thai' => 'required|integer',
        ]);

        $mon = Mon::create([
            'ma_mon' => $request->ma_mon,
            'danh_muc_id' => $request->danh_muc_id,
            'ten_mon' => $request->ten_mon,
            'hinh_anh' => $request->hinh_anh,
            'gia_ban' => $request->gia_ban,
            'cong_thuc' => $request->cong_thuc,
            'trang_thai' => $request->trang_thai
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm món ăn thành công',
            'data' => $mon
        ], 201);
    }

    /**
     * Cập nhật món (Sử dụng ID số)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $mon = Mon::find($id);
        if (!$mon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $request->validate([
            'danh_muc_id' => 'sometimes|required|integer|exists:danh_mucs,id',
            'ten_mon' => 'sometimes|required|string|max:255',
            'gia_ban' => 'sometimes|required|numeric|min:0',
            'trang_thai' => 'sometimes|required|integer',
        ]);

        $mon->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật món ăn thành công',
            'data' => $mon
        ]);
    }

    /**
     * Xóa món
     */
    public function destroy($id): JsonResponse
    {
        $mon = Mon::find($id);
        if (!$mon) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        if ($mon->chiTietDonHangs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa món đang có trong đơn hàng. Hãy chuyển trạng thái sang ngừng bán.'
            ], 400);
        }

        $mon->congThucs()->delete();
        $mon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa món ăn thành công'
        ]);
    }
}
