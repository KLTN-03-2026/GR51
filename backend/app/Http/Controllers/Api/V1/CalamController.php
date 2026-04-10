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

        $maNhanSu = $request->user() ? $request->user()->ma_nhan_su : 'NV01';

        // Kiểm tra xem nhân viên này có ca nào đang mở không (chống mở 2 ca cùng lúc)
        $caLamHienTai = CaLam::where('ma_nhan_su', $maNhanSu)
            ->where('trang_thai', 'dang_lam')
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
            'ma_nhan_su' => $maNhanSu,
            'thoi_gian_bat_dau' => Carbon::now(),
            'thoi_gian_ket_thuc' => null,
            'tien_mat_dau_ca' => $request->input('tien_mat_dau_ca'),
            'tien_mat_he_thong' => $request->input('tien_mat_dau_ca'), // Lúc mới mở ca, tiền hệ thống = tiền đầu ca
            'tong_doanh_thu' => 0,
            'tien_mat_thuc_te' => 0,
            'trang_thai' => 'dang_lam'
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
        // Lấy mã nhân sự (Giả sử đang test chưa có token thì hardcode mã nhân viên đang có trong DB của bạn, ví dụ 'NS01')
        $maNhanSu = $request->user() ? $request->user()->ma_nhan_su : 'NV01';

        // Tìm ca làm đang mở của nhân viên này
        $caLam = CaLam::where('ma_nhan_su', $maNhanSu)
            ->where('trang_thai', 'dang_lam')
            ->first();

        if (!$caLam) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không có ca làm nào đang mở.',
                'data' => null
            ], 404);
        }

        // Kéo tất cả đơn hàng đã Hoàn Thành của nhân viên này trong khoảng thời gian ca làm
        $donHangs = DonHang::where('ma_nhan_su', $maNhanSu)
            ->whereIn('trang_thai_don', ['hoan_thanh', 'hoan_thanh_pha_che'])
            ->where('created_at', '>=', $caLam->thoi_gian_bat_dau)
            ->get();

        // Tính toán các con số để đẩy lên UI
        $tongSoDon = $donHangs->count();
        $tongDoanhThu = $donHangs->sum('tong_tien');
        $tienMat = $donHangs->where('phuong_thuc_thanh_toan', 'tien_mat')->sum('tong_tien');
        $chuyenKhoan = $donHangs->where('phuong_thuc_thanh_toan', 'chuyen_khoan')->sum('tong_tien'); // Hoặc 'the' / 'card' tùy DB bạn quy định
        $trungBinhDon = $tongSoDon > 0 ? $tongDoanhThu / $tongSoDon : 0;
        $nhanVien = \App\Models\NhanSu::where('ma_nhan_su', $maNhanSu)->first();
        $hoTen = $nhanVien ? $nhanVien->ho_ten : 'Nhân viên ẩn danh';
        $vaiTro = $nhanVien ? $nhanVien->vai_tro : 'nhan_vien';

        return response()->json([
            'status' => 'success',
            'data' => [
                'ma_ca_lam' => $caLam->ma_ca_lam,
                'thoi_gian_bat_dau' => $caLam->thoi_gian_bat_dau,
                'nhan_vien' => [
                    'ho_ten' => $hoTen,
                    'vai_tro' => $vaiTro,
                ],
                'thong_ke' => [
                    'tong_doanh_thu' => (float)$tongDoanhThu,
                    'tong_so_don' => $tongSoDon,
                    'trung_binh_don' => round((float)$trungBinhDon, 2),
                    'tien_mat' => (float)$tienMat,
                    'chuyen_khoan' => (float)$chuyenKhoan
                ]
            ]
        ], 200);
    }

    // 2. API Thực hiện Kết ca
    public function closeShift(Request $request)
    {
        $maNhanSu = $request->user() ? $request->user()->ma_nhan_su : 'NV01';

        try {
            DB::beginTransaction();

            $caLam = CaLam::where('ma_nhan_su', $maNhanSu)
                ->where('trang_thai', 'dang_lam')
                ->first();

            if (!$caLam) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Không tìm thấy ca làm đang mở để kết thúc.'
                ], 404);
            }

            // Tính toán lại tiền hệ thống trước khi chốt
            $tienMatHeThong = DonHang::where('ma_nhan_su', $maNhanSu)
                ->whereIn('trang_thai_don', ['hoan_thanh', 'hoan_thanh_pha_che'])
                ->where('phuong_thuc_thanh_toan', 'tien_mat')
                ->where('created_at', '>=', $caLam->thoi_gian_bat_dau)
                ->sum('tong_tien');

            $tongDoanhThu = DonHang::where('ma_nhan_su', $maNhanSu)
                ->where('trang_thai_don', 'hoan_thanh', 'hoan_thanh_pha_che')
                ->where('created_at', '>=', $caLam->thoi_gian_bat_dau)
                ->sum('tong_tien');

            // Cập nhật chốt sổ ca làm
            $caLam->thoi_gian_ket_thuc = Carbon::now();
            $caLam->tien_mat_he_thong = $tienMatHeThong + $caLam->tien_mat_dau_ca; // Tiền mặt trong két = Tiền đầu ca + Tiền bán được
            $caLam->tong_doanh_thu = $tongDoanhThu;
            $caLam->trang_thai = 'da_ket_thuc';
            $caLam->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Kết ca thành công!',
                'data' => $caLam
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
