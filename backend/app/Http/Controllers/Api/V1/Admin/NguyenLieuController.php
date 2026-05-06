<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\NguyenLieu;
use App\Models\LichSuKho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NguyenLieuController extends Controller
{
    /**
     * Danh sách nguyên liệu
     */
    public function index(Request $request): JsonResponse
    {
        $query = NguyenLieu::query();

        if ($request->has('trang_thai') && $request->trang_thai !== null) {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->has('tinh_trang_kho')) {
            switch ($request->tinh_trang_kho) {
                case 'het_hang':
                    $query->where('ton_kho', '<=', 0);
                    break;
                case 'sap_het':
                    $query->where('ton_kho', '>', 0)->whereColumn('ton_kho', '<=', 'muc_canh_bao');
                    break;
                case 'con_hang':
                    $query->whereColumn('ton_kho', '>', 'muc_canh_bao');
                    break;
            }
        }

        if ($request->has('search') && $request->search) {
            $query->where('ten_nguyen_lieu', 'LIKE', '%' . $request->search . '%');
        }

        $nguyenLieus = $query->orderByRaw("
            CASE 
                WHEN ton_kho <= 0 THEN 1
                WHEN ton_kho <= muc_canh_bao THEN 2
                ELSE 3
            END ASC
        ")->orderBy('ten_nguyen_lieu')->get()->map(function ($nl) {
            $tinhTrang = 'con_hang';
            if ($nl->ton_kho <= 0) $tinhTrang = 'het_hang';
            elseif ($nl->ton_kho <= $nl->muc_canh_bao) $tinhTrang = 'sap_het';

            return [
                'id' => $nl->id,
                'ma_nguyen_lieu' => $nl->ma_nguyen_lieu,
                'ten_nguyen_lieu' => $nl->ten_nguyen_lieu,
                'hinh_anh' => $nl->hinh_anh,
                'don_vi_tinh' => $nl->don_vi_tinh,
                'ton_kho' => (float) $nl->ton_kho,
                'muc_canh_bao' => (float) $nl->muc_canh_bao,
                'trang_thai' => (int)$nl->trang_thai,
                'tinh_trang_kho' => $tinhTrang,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $nguyenLieus
        ]);
    }

    /**
     * Thêm nguyên liệu
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_nguyen_lieu' => 'required|string|unique:nguyen_lieus,ma_nguyen_lieu',
            'ten_nguyen_lieu' => 'required|string|max:255',
            'don_vi_tinh' => 'required|string|max:50',
            'ton_kho' => 'required|numeric|min:0',
            'muc_canh_bao' => 'required|numeric|min:0',
            'trang_thai' => 'required|integer',
        ]);

        $nguyenLieu = NguyenLieu::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Thêm nguyên liệu thành công',
            'data' => $nguyenLieu
        ], 201);
    }

    /**
     * Cập nhật nguyên liệu
     */
    public function update(Request $request, $id): JsonResponse
    {
        $nguyenLieu = NguyenLieu::find($id);
        if (!$nguyenLieu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);
        }

        $request->validate([
            'ten_nguyen_lieu' => 'sometimes|required|string|max:255',
            'muc_canh_bao' => 'sometimes|required|numeric|min:0',
            'trang_thai' => 'sometimes|required|integer',
        ]);

        $nguyenLieu->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $nguyenLieu
        ]);
    }

    /**
     * Xóa nguyên liệu
     */
    public function destroy($id): JsonResponse
    {
        $nguyenLieu = NguyenLieu::find($id);
        if (!$nguyenLieu) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        if ($nguyenLieu->congThucs()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Đang được sử dụng trong công thức'], 400);
        }

        $nguyenLieu->lichSuKhos()->delete();
        $nguyenLieu->delete();

        return response()->json(['success' => true, 'message' => 'Xóa thành công']);
    }

    /**
     * Nhập kho
     */
    public function nhapKho(Request $request, $id): JsonResponse
    {
        $request->validate(['so_luong' => 'required|numeric|min:0.01']);

        $nguyenLieu = NguyenLieu::find($id);
        if (!$nguyenLieu) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        try {
            DB::beginTransaction();
            $soLuong = $request->input('so_luong');
            $nguyenLieu->increment('ton_kho', $soLuong);

            LichSuKho::create([
                'ma_ls_kho' => 'LSK_' . uniqid(),
                'nguyen_lieu_id' => $id,
                'nhan_su_id' => $request->user()->id,
                'loai_giao_dich' => 1, // 1: Nhập
                'so_luong_thay_doi' => $soLuong,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Nhập kho thành công']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Lịch sử kho
     */
    public function lichSuKho(Request $request): JsonResponse
    {
        $query = LichSuKho::with(['nguyenLieu', 'nhanSu']);

        if ($request->has('nguyen_lieu_id') && $request->nguyen_lieu_id) {
            $query->where('nguyen_lieu_id', $request->nguyen_lieu_id);
        }

        $lichSu = $query->orderByDesc('created_at')->limit(100)->get()->map(function ($ls) {
            return [
                'id' => $ls->id,
                'ten_nguyen_lieu' => $ls->nguyenLieu ? $ls->nguyenLieu->ten_nguyen_lieu : 'N/A',
                'nhan_vien' => $ls->nhanSu ? $ls->nhanSu->ho_ten : 'Hệ thống',
                'loai_giao_dich' => (int)$ls->loai_giao_dich,
                'so_luong_thay_doi' => (float) $ls->so_luong_thay_doi,
                'thoi_gian' => $ls->created_at ? $ls->created_at->format('H:i d/m/Y') : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $lichSu]);
    }
}
