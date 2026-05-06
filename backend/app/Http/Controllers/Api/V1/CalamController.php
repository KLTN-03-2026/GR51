<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CaLam;
use App\Models\DonHang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CaLamController extends Controller
{
    // 3. API Thực hiện Mở ca
    public function openShift(Request $request)
    {
        $request->validate([
            'tien_mat_dau_ca' => 'required|numeric|min:0'
        ]);

        $nhanSuId = $request->user()->id;
        $maNhanSu = $request->user()->ma_nhan_su; // Dùng để tạo mã ca làm cho đẹp

        // Kiểm tra xem nhân viên này có ca nào đang mở không (chống mở 2 ca cùng lúc)
        $caLamHienTai = CaLam::where('nhan_su_id', $nhanSuId)
            ->where('trang_thai', 1)
            ->first();

        if ($caLamHienTai) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bạn đang có một ca làm chưa kết thúc!'
            ], 400);
        }

        // Tạo mã ca làm mới (Ví dụ: CL_NS01_1712456789)
        $maCaLam = 'CL_' . $maNhanSu . '_' . time();

        $caLamMoi = CaLam::create([
            'ma_ca_lam' => $maCaLam,
            'nhan_su_id' => $nhanSuId,
            'thoi_gian_bat_dau' => Carbon::now(),
            'thoi_gian_ket_thuc' => null,
            'tien_mat_dau_ca' => $request->input('tien_mat_dau_ca'),
            'tien_mat_he_thong' => $request->input('tien_mat_dau_ca'), 
            'tong_doanh_thu' => 0,
            'tien_mat_thuc_te' => 0,
            'trang_thai' => 1 // 1: Đang làm
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mở ca thành công!',
            'data' => $caLamMoi
        ], 201);
    }

    // 1. API Lấy thông tin ca làm hiện tại và thống kê doanh thu
    public function getCurrentShift(Request $request)
    {
        $nhanSuId = $request->user()->id;

        // Tìm ca làm đang mở của nhân viên này
        $caLam = CaLam::where('nhan_su_id', $nhanSuId)
            ->where('trang_thai', 1)
            ->first();

        if (!$caLam) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không có ca làm nào đang mở.',
                'data' => null
            ], 404);
        }

        // Kéo tất cả đơn hàng đã thanh toán trong ca
        $startTime = Carbon::parse($caLam->thoi_gian_bat_dau);
        
        $donHangs = DonHang::where(function($q) use ($nhanSuId) {
                $q->where('nhan_su_id', $nhanSuId)
                  ->orWhereNull('nhan_su_id');
            })
            ->where('trang_thai_thanh_toan', 1) 
            ->where('created_at', '>=', $startTime)
            ->get();

        // Đếm đơn đang xử lý (1: Đang pha, 0: Chờ xử lý)
        $donDangXuLy = DonHang::where('nhan_su_id', $nhanSuId)
            ->whereIn('trang_thai_don', [0, 1])
            ->where('created_at', '>=', $startTime)
            ->count();

        $tongSoDon = $donHangs->count();
        $tongDoanhThu = $donHangs->sum('tong_tien');
        $tienMat = $donHangs->where('phuong_thuc_thanh_toan', 'tien_mat')->sum('tong_tien');
        $chuyenKhoan = $donHangs->where('phuong_thuc_thanh_toan', 'chuyen_khoan')->sum('tong_tien');
        $trungBinhDon = $tongSoDon > 0 ? $tongDoanhThu / $tongSoDon : 0;
        
        $hoTen = $request->user()->ho_ten;
        $vaiTro = $request->user()->vai_tro;

        $tienMatHeThong = (float)$caLam->tien_mat_dau_ca + (float)$tienMat;

        return response()->json([
            'status' => 'success',
            'data' => [
                'ma_ca_lam' => $caLam->ma_ca_lam,
                'thoi_gian_bat_dau' => $caLam->thoi_gian_bat_dau,
                'tien_mat_dau_ca' => (float)$caLam->tien_mat_dau_ca,
                'tien_mat_he_thong' => $tienMatHeThong,
                'nhan_vien' => [
                    'ho_ten' => $hoTen,
                    'vai_tro' => $vaiTro,
                ],
                'thong_ke' => [
                    'tong_doanh_thu' => (float)$tongDoanhThu,
                    'tong_so_don' => $tongSoDon,
                    'trung_binh_don' => round((float)$trungBinhDon, 0),
                    'tien_mat' => (float)$tienMat,
                    'chuyen_khoan' => (float)$chuyenKhoan,
                    'don_dang_xu_ly' => $donDangXuLy,
                ]
            ]
        ], 200);
    }

    // 2. API Thực hiện Kết ca
    public function closeShift(Request $request)
    {
        $request->validate([
            'tien_mat_thuc_te' => 'required|numeric|min:0',
            'ghi_chu' => 'nullable|string|max:500',
        ]);

        $nhanSuId = $request->user()->id;

        try {
            DB::beginTransaction();

            $caLam = CaLam::where('nhan_su_id', $nhanSuId)
                ->where('trang_thai', 1)
                ->first();

            if (!$caLam) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy ca làm đang mở để kết thúc.'
                ], 404);
            }

            // Kiểm tra đơn đang xử lý (0, 1)
            $donDangXuLy = DonHang::where('nhan_su_id', $nhanSuId)
                ->whereIn('trang_thai_don', [0, 1])
                ->where('created_at', '>=', $caLam->thoi_gian_bat_dau)
                ->count();

            if ($donDangXuLy > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => "Còn $donDangXuLy đơn hàng đang xử lý. Vui lòng hoàn thành trước khi kết ca.",
                    'don_dang_xu_ly' => $donDangXuLy,
                ], 400);
            }

            $startTime = Carbon::parse($caLam->thoi_gian_bat_dau);

            $queryShiftOrders = DonHang::where(function($q) use ($nhanSuId) {
                    $q->where('nhan_su_id', $nhanSuId)
                      ->orWhereNull('nhan_su_id');
                })
                ->where('trang_thai_thanh_toan', 1)
                ->where('created_at', '>=', $startTime);

            $tienMatBanDuoc = (clone $queryShiftOrders)
                ->where('phuong_thuc_thanh_toan', 'tien_mat')
                ->sum('tong_tien');

            $tongDoanhThu = (clone $queryShiftOrders)->sum('tong_tien');

            $tienMatHeThong = $tienMatBanDuoc + $caLam->tien_mat_dau_ca;
            $tienMatThucTe = $request->input('tien_mat_thuc_te', 0);
            $chenhLech = $tienMatThucTe - $tienMatHeThong;

            $caLam->thoi_gian_ket_thuc = Carbon::now();
            $caLam->tien_mat_he_thong = $tienMatHeThong;
            $caLam->tien_mat_thuc_te = $tienMatThucTe;
            $caLam->tong_doanh_thu = $tongDoanhThu;
            $caLam->ghi_chu = $request->input('ghi_chu');
            $caLam->trang_thai = 0; // 0: Đã kết thúc
            $caLam->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Kết ca thành công!',
                'data' => [
                    'ca_lam' => $caLam,
                    'chenh_lech' => $chenhLech,
                    'tien_mat_he_thong' => (float)$tienMatHeThong,
                    'tien_mat_thuc_te' => (float)$tienMatThucTe,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi kết ca: ' . $e->getMessage()
            ], 500);
        }
    }
}
