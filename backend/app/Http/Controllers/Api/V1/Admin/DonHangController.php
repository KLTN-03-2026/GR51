<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonHangController extends Controller
{
    /**
     * Danh sách đơn hàng (filter + phân trang)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DonHang::with([
                'chiTietDonHangs.mon:ma_mon,ten_mon,gia_ban',
                'chiTietDonHangs.kichCo:ma_kich_co,ten_kich_co',
                'chiTietDonHangs.chiTietToppings.topping:ma_topping,ten_topping,gia_tien',
                'ban:ma_ban,ten_ban',
                'nhanSu:ma_nhan_su,ho_ten',
                'danhGia:ma_danh_gia,ma_don_hang,so_sao,binh_luan',
            ]);

            // Filter theo ngày
            if ($request->has('tu_ngay') && $request->tu_ngay) {
                $query->whereDate('created_at', '>=', $request->tu_ngay);
            }
            if ($request->has('den_ngay') && $request->den_ngay) {
                $query->whereDate('created_at', '<=', $request->den_ngay);
            }

            // Nếu không truyền ngày, mặc định lấy hôm nay
            if (!$request->has('tu_ngay') && !$request->has('den_ngay')) {
                $query->whereDate('created_at', \Carbon\Carbon::today());
            }

            // Filter trạng thái đơn
            if ($request->has('trang_thai_don') && $request->trang_thai_don) {
                $query->where('trang_thai_don', $request->trang_thai_don);
            }

            // Filter trạng thái thanh toán
            if ($request->has('trang_thai_thanh_toan') && $request->trang_thai_thanh_toan) {
                $query->where('trang_thai_thanh_toan', $request->trang_thai_thanh_toan);
            }

            // Filter phương thức thanh toán
            if ($request->has('phuong_thuc_thanh_toan') && $request->phuong_thuc_thanh_toan) {
                $query->where('phuong_thuc_thanh_toan', $request->phuong_thuc_thanh_toan);
            }

            // Filter loại đơn
            if ($request->has('loai_don') && $request->loai_don) {
                $query->where('loai_don', $request->loai_don);
            }

            // Tìm kiếm theo mã đơn hàng
            if ($request->has('search') && $request->search) {
                $query->where('ma_don_hang', 'LIKE', '%' . $request->search . '%');
            }

            $donHangs = $query->orderByDesc('created_at')->get();

            // Tính tổng doanh thu theo filter
            $tongDoanhThu = $donHangs->where('trang_thai_thanh_toan', 'da_thanh_toan')->sum('tong_tien');

            $data = $donHangs->map(function ($dh) {
                return [
                    'ma_don_hang' => $dh->ma_don_hang,
                    'ten_ban' => $dh->ban ? $dh->ban->ten_ban : 'Mang đi',
                    'nhan_vien' => $dh->nhanSu ? $dh->nhanSu->ho_ten : 'Khách QR',
                    'loai_don' => $dh->loai_don,
                    'tong_tien' => (float) $dh->tong_tien,
                    'phuong_thuc_thanh_toan' => $dh->phuong_thuc_thanh_toan,
                    'trang_thai_thanh_toan' => $dh->trang_thai_thanh_toan,
                    'trang_thai_don' => $dh->trang_thai_don,
                    'danh_gia' => $dh->danhGia ? [
                        'so_sao' => $dh->danhGia->so_sao,
                        'binh_luan' => $dh->danhGia->binh_luan,
                    ] : null,
                    'chi_tiets' => $dh->chiTietDonHangs->map(function ($ct) {
                        return [
                            'ma_chi_tiet' => $ct->ma_chi_tiet,
                            'ten_mon' => $ct->mon ? $ct->mon->ten_mon : 'N/A',
                            'ten_kich_co' => $ct->kichCo ? $ct->kichCo->ten_kich_co : null,
                            'so_luong' => $ct->so_luong,
                            'don_gia' => (float) $ct->don_gia,
                            'ghi_chu' => $ct->ghi_chu,
                            'toppings' => $ct->chiTietToppings->map(function ($ctt) {
                                return $ctt->topping ? $ctt->topping->ten_topping : null;
                            })->filter()->values(),
                        ];
                    }),
                    'thoi_gian' => $dh->created_at ? $dh->created_at->format('H:i d/m/Y') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'don_hangs' => $data,
                    'tong_hop' => [
                        'tong_don' => $donHangs->count(),
                        'tong_doanh_thu' => (float) $tongDoanhThu,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách đơn hàng: ' . $e->getMessage()
            ], 500);
        }
    }
}
