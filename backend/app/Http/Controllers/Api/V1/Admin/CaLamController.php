<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaLam;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaLamController extends Controller
{
    /**
     * Lịch sử ca làm (filter + chi tiết)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CaLam::with('nhanSu');

            if ($request->has('nhan_su_id') && $request->nhan_su_id) {
                $query->where('nhan_su_id', $request->nhan_su_id);
            }

            if ($request->has('trang_thai') && $request->trang_thai !== null) {
                $query->where('trang_thai', $request->trang_thai);
            }

            $caLams = $query->orderByDesc('thoi_gian_bat_dau')
                ->limit(100)
                ->get()
                ->map(function ($cl) {
                    $donHangs = DonHang::where('nhan_su_id', $cl->nhan_su_id)
                        ->where('trang_thai_thanh_toan', 1) // 1: Đã thanh toán
                        ->where('created_at', '>=', $cl->thoi_gian_bat_dau);
                    
                    if ($cl->thoi_gian_ket_thuc) {
                        $donHangs->where('created_at', '<=', $cl->thoi_gian_ket_thuc);
                    }

                    $tongDon = $donHangs->count();
                    $tienMat = (clone $donHangs)->where('phuong_thuc_thanh_toan', 'tien_mat')->sum('tong_tien');
                    $chuyenKhoan = (clone $donHangs)->where('phuong_thuc_thanh_toan', 'chuyen_khoan')->sum('tong_tien');

                    $tongDoanhThu = (float)($tienMat + $chuyenKhoan);
                    $trungBinh = $tongDon > 0 ? round($tongDoanhThu / $tongDon, 0) : 0;

                    // Đếm đơn đang xử lý (0: Chờ, 1: Đang pha)
                    $donDangXuLy = DonHang::where('nhan_su_id', $cl->nhan_su_id)
                        ->whereIn('trang_thai_don', [0, 1])
                        ->where('created_at', '>=', $cl->thoi_gian_bat_dau);
                    if ($cl->thoi_gian_ket_thuc) {
                        $donDangXuLy->where('created_at', '<=', $cl->thoi_gian_ket_thuc);
                    }
                    $countDangXuLy = $donDangXuLy->count();

                    return [
                        'id' => $cl->id,
                        'ma_ca_lam' => $cl->ma_ca_lam,
                        'nhan_vien' => $cl->nhanSu ? [
                            'id' => $cl->nhanSu->id,
                            'ho_ten' => $cl->nhanSu->ho_ten,
                            'vai_tro' => $cl->nhanSu->vai_tro
                        ] : null,
                        'thoi_gian_bat_dau' => $cl->thoi_gian_bat_dau,
                        'thoi_gian_ket_thuc' => $cl->thoi_gian_ket_thuc,
                        'tong_doanh_thu' => $cl->trang_thai == 0 ? (float)$cl->tong_doanh_thu : $tongDoanhThu,
                        'tien_mat_dau_ca' => (float)$cl->tien_mat_dau_ca,
                        'tien_mat_he_thong' => (float)$cl->tien_mat_he_thong,
                        'trang_thai' => (int)$cl->trang_thai,
                        'thong_ke' => [
                            'tong_so_don' => $tongDon,
                            'tien_mat' => (float) $tienMat,
                            'chuyen_khoan' => (float) $chuyenKhoan,
                            'tong_doanh_thu' => $tongDoanhThu,
                            'trung_binh_don' => (float) $trungBinh,
                            'don_dang_xu_ly' => $countDangXuLy
                        ]
                    ];
                });

            return response()->json(['success' => true, 'data' => $caLams]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
