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
     * Danh sách nguyên liệu (có filter)
     */
    public function index(Request $request): JsonResponse
    {
        $query = NguyenLieu::query();

        // Filter trạng thái
        if ($request->has('trang_thai') && $request->trang_thai) {
            $query->where('trang_thai', $request->trang_thai);
        }

        // Filter tình trạng kho
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

        // Tìm kiếm
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
                'ma_nguyen_lieu' => $nl->ma_nguyen_lieu,
                'ten_nguyen_lieu' => $nl->ten_nguyen_lieu,
                'hinh_anh' => $nl->hinh_anh,
                'don_vi_tinh' => $nl->don_vi_tinh,
                'ton_kho' => (float) $nl->ton_kho,
                'muc_canh_bao' => (float) $nl->muc_canh_bao,
                'trang_thai' => $nl->trang_thai,
                'tinh_trang_kho' => $tinhTrang,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $nguyenLieus
        ]);
    }

    /**
     * Thêm nguyên liệu mới
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_nguyen_lieu' => 'required|string|unique:nguyen_lieus,ma_nguyen_lieu',
            'ten_nguyen_lieu' => 'required|string|max:255',
            'don_vi_tinh' => 'required|string|max:50',
            'ton_kho' => 'required|numeric|min:0',
            'muc_canh_bao' => 'required|numeric|min:0',
            'trang_thai' => 'required|string',
            'hinh_anh' => 'nullable|string',
        ]);

        $nguyenLieu = NguyenLieu::create($request->only([
            'ma_nguyen_lieu', 'ten_nguyen_lieu', 'hinh_anh', 'don_vi_tinh', 'ton_kho', 'muc_canh_bao', 'trang_thai'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Thêm nguyên liệu thành công',
            'data' => $nguyenLieu
        ], 201);
    }

    /**
     * Cập nhật nguyên liệu
     */
    public function update(Request $request, $maNguyenLieu): JsonResponse
    {
        $nguyenLieu = NguyenLieu::find($maNguyenLieu);
        if (!$nguyenLieu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
        }

        $request->validate([
            'ten_nguyen_lieu' => 'sometimes|required|string|max:255',
            'don_vi_tinh' => 'sometimes|required|string|max:50',
            'muc_canh_bao' => 'sometimes|required|numeric|min:0',
            'trang_thai' => 'sometimes|required|string',
            'hinh_anh' => 'nullable|string',
        ]);

        $nguyenLieu->update($request->only([
            'ten_nguyen_lieu', 'hinh_anh', 'don_vi_tinh', 'muc_canh_bao', 'trang_thai'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật nguyên liệu thành công',
            'data' => $nguyenLieu
        ]);
    }

    /**
     * Xóa nguyên liệu
     */
    public function destroy($maNguyenLieu): JsonResponse
    {
        $nguyenLieu = NguyenLieu::find($maNguyenLieu);
        if (!$nguyenLieu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
        }

        if ($nguyenLieu->congThucs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa nguyên liệu đang được sử dụng trong công thức.'
            ], 400);
        }

        $nguyenLieu->lichSuKhos()->delete();
        $nguyenLieu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa nguyên liệu thành công'
        ]);
    }

    /**
     * Nhập kho cho nguyên liệu
     */
    public function nhapKho(Request $request, $maNguyenLieu): JsonResponse
    {
        $request->validate([
            'so_luong' => 'required|numeric|min:0.01',
            'ghi_chu' => 'nullable|string|max:500',
        ]);

        $nguyenLieu = NguyenLieu::find($maNguyenLieu);
        if (!$nguyenLieu) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
        }

        try {
            DB::beginTransaction();

            $soLuong = $request->input('so_luong');

            // Tăng tồn kho
            $nguyenLieu->increment('ton_kho', $soLuong);

            // Ghi lịch sử kho
            LichSuKho::create([
                'ma_ls_kho' => 'LSK_' . uniqid(),
                'ma_nguyen_lieu' => $maNguyenLieu,
                'ma_nhan_su' => $request->user()->ma_nhan_su,
                'loai_giao_dich' => 'NHAP_KHO',
                'so_luong_thay_doi' => $soLuong,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Nhập kho thành công: +{$soLuong} {$nguyenLieu->don_vi_tinh}",
                'data' => [
                    'ton_kho_moi' => (float) $nguyenLieu->fresh()->ton_kho,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập kho: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lịch sử xuất/nhập kho
     */
    public function lichSuKho(Request $request): JsonResponse
    {
        $query = LichSuKho::with([
            'nguyenLieu:ma_nguyen_lieu,ten_nguyen_lieu,don_vi_tinh',
            'nhanSu:ma_nhan_su,ho_ten'
        ]);

        // Filter theo nguyên liệu
        if ($request->has('ma_nguyen_lieu') && $request->ma_nguyen_lieu) {
            $query->where('ma_nguyen_lieu', $request->ma_nguyen_lieu);
        }

        // Filter theo loại giao dịch
        if ($request->has('loai_giao_dich') && $request->loai_giao_dich) {
            $query->where('loai_giao_dich', $request->loai_giao_dich);
        }

        // Filter theo ngày
        if ($request->has('tu_ngay') && $request->tu_ngay) {
            $query->whereDate('created_at', '>=', $request->tu_ngay);
        }
        if ($request->has('den_ngay') && $request->den_ngay) {
            $query->whereDate('created_at', '<=', $request->den_ngay);
        }

        $lichSu = $query->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->map(function ($ls) {
                return [
                    'ma_ls_kho' => $ls->ma_ls_kho,
                    'ma_nguyen_lieu' => $ls->ma_nguyen_lieu,
                    'ten_nguyen_lieu' => $ls->nguyenLieu ? $ls->nguyenLieu->ten_nguyen_lieu : null,
                    'don_vi_tinh' => $ls->nguyenLieu ? $ls->nguyenLieu->don_vi_tinh : null,
                    'nhan_vien' => $ls->nhanSu ? $ls->nhanSu->ho_ten : 'Hệ thống',
                    'loai_giao_dich' => $ls->loai_giao_dich,
                    'so_luong_thay_doi' => (float) $ls->so_luong_thay_doi,
                    'thoi_gian' => $ls->created_at ? $ls->created_at->format('H:i d/m/Y') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $lichSu
        ]);
    }
}
