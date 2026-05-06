<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\NguyenLieu;
use App\Models\ChiTietDonHang;
use App\Models\DanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * API Thống kê tổng quan cho Dashboard Admin
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today();

            // === 1. Thống kê hôm nay ===
            $donHangsHomNay = DonHang::whereDate('created_at', $today)->get();

            $tongDoanhThuHomNay = $donHangsHomNay
                ->where('trang_thai_thanh_toan', 1) // 1: Đã thanh toán
                ->sum('tong_tien');

            $tongDonHomNay = $donHangsHomNay->count();

            $trungBinhDon = $tongDonHomNay > 0
                ? round($tongDoanhThuHomNay / $tongDonHomNay, 0)
                : 0;

            // === 2. Nguyên liệu cảnh báo ===
            $nguyenLieuSapHet = NguyenLieu::where('trang_thai', 1) // 1: Hoạt động
                ->whereColumn('ton_kho', '<=', 'muc_canh_bao')
                ->where('ton_kho', '>', 0)
                ->count();

            $nguyenLieuHetHang = NguyenLieu::where('trang_thai', 1)
                ->where('ton_kho', '<=', 0)
                ->count();

            // === 3. Doanh thu 7 ngày gần nhất ===
            $doanhThu7Ngay = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $revenue = DonHang::whereDate('created_at', $date)
                    ->where('trang_thai_thanh_toan', 1)
                    ->sum('tong_tien');

                $doanhThu7Ngay[] = [
                    'ngay' => $date->format('d/m'),
                    'doanh_thu' => (float) $revenue
                ];
            }

            // === 4. Đơn hàng theo trạng thái (hôm nay) ===
            $donTheoTrangThai = [
                'cho_xu_ly' => $donHangsHomNay->where('trang_thai_don', 0)->count(),
                'dang_pha' => $donHangsHomNay->where('trang_thai_don', 1)->count(),
                'hoan_thanh' => $donHangsHomNay->where('trang_thai_don', 2)->count(),
                'da_huy' => $donHangsHomNay->where('trang_thai_don', 3)->count(),
            ];

            // === 5. Top 5 món bán chạy hôm nay ===
            $topMonBanChay = ChiTietDonHang::select('mon_id', DB::raw('SUM(so_luong) as tong_ban'))
                ->whereHas('donHang', function ($q) use ($today) {
                    $q->whereDate('created_at', $today);
                })
                ->groupBy('mon_id')
                ->orderByDesc('tong_ban')
                ->limit(5)
                ->with('mon')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->mon_id,
                        'ten_mon' => $item->mon ? $item->mon->ten_mon : 'N/A',
                        'hinh_anh' => $item->mon ? $item->mon->hinh_anh : null,
                        'gia_ban' => $item->mon ? (float) $item->mon->gia_ban : 0,
                        'tong_ban' => (int) $item->tong_ban,
                    ];
                });

            // === 6. 5 đơn hàng gần đây nhất ===
            $donHangGanDay = DonHang::with(['ban', 'nhanSu'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($dh) {
                    return [
                        'id' => $dh->id,
                        'ma_don_hang' => $dh->ma_don_hang,
                        'ten_ban' => $dh->ban ? $dh->ban->ten_ban : 'Mang đi',
                        'nhan_vien' => $dh->nhanSu ? $dh->nhanSu->ho_ten : 'Khách QR',
                        'tong_tien' => (float) $dh->tong_tien,
                        'trang_thai_don' => (int)$dh->trang_thai_don,
                        'trang_thai_thanh_toan' => (int)$dh->trang_thai_thanh_toan,
                        'thoi_gian' => $dh->created_at->format('H:i d/m'),
                    ];
                });

            // === 7. Cảnh báo tồn kho chi tiết ===
            $canhBaoTonKho = NguyenLieu::where('trang_thai', 1)
                ->where(function ($q) {
                    $q->where('ton_kho', '<=', 0)
                      ->orWhereColumn('ton_kho', '<=', 'muc_canh_bao');
                })
                ->orderBy('ton_kho', 'asc')
                ->limit(10)
                ->get()
                ->map(function ($nl) {
                    return [
                        'id' => $nl->id,
                        'ten_nguyen_lieu' => $nl->ten_nguyen_lieu,
                        'don_vi_tinh' => $nl->don_vi_tinh,
                        'ton_kho' => (float) $nl->ton_kho,
                        'muc_canh_bao' => (float) $nl->muc_canh_bao,
                        'trang_thai_kho' => $nl->ton_kho <= 0 ? 'het_hang' : 'sap_het',
                    ];
                });

            // === 8. Đánh giá trung bình ===
            $danhGiaTB = DanhGia::avg('so_sao');

            // === 9. Thống kê tổng hợp (tất cả thời gian) ===
            $tongDoanhThuAll = DonHang::where('trang_thai_thanh_toan', 1)->sum('tong_tien');
            $tongDonAll = DonHang::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'thong_ke_hom_nay' => [
                        'tong_doanh_thu' => (float) $tongDoanhThuHomNay,
                        'tong_don' => $tongDonHomNay,
                        'trung_binh_don' => (float) $trungBinhDon,
                        'nguyen_lieu_sap_het' => $nguyenLieuSapHet,
                        'nguyen_lieu_het_hang' => $nguyenLieuHetHang,
                    ],
                    'thong_ke_tong_hop' => [
                        'tong_doanh_thu' => (float) $tongDoanhThuAll,
                        'tong_don' => $tongDonAll,
                        'danh_gia_trung_binh' => $danhGiaTB ? round((float) $danhGiaTB, 1) : null,
                    ],
                    'doanh_thu_7_ngay' => $doanhThu7Ngay,
                    'don_theo_trang_thai' => $donTheoTrangThai,
                    'top_mon_ban_chay' => $topMonBanChay,
                    'don_hang_gan_day' => $donHangGanDay,
                    'canh_bao_ton_kho' => $canhBaoTonKho,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy dữ liệu dashboard: ' . $e->getMessage()
            ], 500);
        }
    }
}
