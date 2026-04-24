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
            $query = CaLam::with('nhanSu:ma_nhan_su,ho_ten,vai_tro');

            // Filter theo nhân viên
            if ($request->has('ma_nhan_su') && $request->ma_nhan_su) {
                $query->where('ma_nhan_su', $request->ma_nhan_su);
            }

            // Filter theo trạng thái
            if ($request->has('trang_thai') && $request->trang_thai) {
                $query->where('trang_thai', $request->trang_thai);
            }

            // Filter theo ngày
            if ($request->has('tu_ngay') && $request->tu_ngay) {
                $query->whereDate('thoi_gian_bat_dau', '>=', $request->tu_ngay);
            }
            if ($request->has('den_ngay') && $request->den_ngay) {
                $query->whereDate('thoi_gian_bat_dau', '<=', $request->den_ngay);
            }

            $caLams = $query->orderByDesc('thoi_gian_bat_dau')
                ->limit(100)
                ->get()
                ->map(function ($cl) {
                    // Tính thống kê đơn hàng trong ca
                    $donHangs = DonHang::where('ma_nhan_su', $cl->ma_nhan_su)
                        ->where('trang_thai_don', 'hoan_thanh')
                        ->where('created_at', '>=', $cl->thoi_gian_bat_dau);
                    
                    if ($cl->thoi_gian_ket_thuc) {
                        $donHangs->where('created_at', '<=', $cl->thoi_gian_ket_thuc);
                    }

                    $tongDon = $donHangs->count();
                    $tienMat = (clone $donHangs)->where('phuong_thuc_thanh_toan', 'tien_mat')->sum('tong_tien');
                    $chuyenKhoan = (clone $donHangs)->where('phuong_thuc_thanh_toan', 'chuyen_khoan')->sum('tong_tien');

                    return [
                        'ma_ca_lam' => $cl->ma_ca_lam,
                        'nhan_vien' => $cl->nhanSu ? $cl->nhanSu->ho_ten : 'N/A',
                        'ma_nhan_su' => $cl->ma_nhan_su,
                        'thoi_gian_bat_dau' => $cl->thoi_gian_bat_dau,
                        'thoi_gian_ket_thuc' => $cl->thoi_gian_ket_thuc,
                        'tien_mat_dau_ca' => (float) $cl->tien_mat_dau_ca,
                        'tien_mat_he_thong' => (float) $cl->tien_mat_he_thong,
                        'tien_mat_thuc_te' => $cl->tien_mat_thuc_te ? (float) $cl->tien_mat_thuc_te : null,
                        'tong_doanh_thu' => (float) $cl->tong_doanh_thu,
                        'ghi_chu' => $cl->ghi_chu,
                        'trang_thai' => $cl->trang_thai,
                        'thong_ke' => [
                            'tong_don' => $tongDon,
                            'tien_mat' => (float) $tienMat,
                            'chuyen_khoan' => (float) $chuyenKhoan,
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $caLams
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử ca làm: ' . $e->getMessage()
            ], 500);
        }
    }
}
