<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\NguyenLieu;
use App\Models\LichSuKho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonHangController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'chi_tiets.*.ghi_chu' => 'nullable|string|max:255',
                'loai_don' => 'required|in:tai_ban,mang_di',
                'phuong_thuc_thanh_toan' => 'required|in:tien_mat,chuyen_khoan,momo',
                'trang_thai_thanh_toan' => 'required|in:chua_thanh_toan,da_thanh_toan',
            ]);

            DB::beginTransaction();

            $chiTiets = $request->input('chi_tiets', []);
            $tongTien = 0;

            foreach ($chiTiets as $item) {
                $tongTien += ($item['so_luong'] * $item['don_gia']);
            }

            $maDonHang = uniqid('DH_');

            $trangThaiDon = $request->input('trang_thai_don');
            if (!in_array($trangThaiDon, ['dang_pha', 'cho_xac_nhan', 'cho_xu_ly'])) {
                $trangThaiDon = 'dang_pha';
            }

            $donHang = DonHang::create([
                'ma_don_hang' => $maDonHang,
                'ma_ban' => $request->input('ma_ban'),
                'ma_nhan_su' => $request->user()->ma_nhan_su,
                'loai_don' => $request->input('loai_don'),
                'tong_tien' => $tongTien,
                'phuong_thuc_thanh_toan' => $request->input('phuong_thuc_thanh_toan'),
                'trang_thai_thanh_toan' => $request->input('trang_thai_thanh_toan'),
                'trang_thai_don' => $trangThaiDon,
            ]);

            foreach ($chiTiets as $item) {
                $maChiTiet = uniqid('CT_');
                ChiTietDonHang::create([
                    'ma_chi_tiet' => $maChiTiet,
                    'ma_don_hang' => $maDonHang,
                    'ma_mon' => $item['ma_mon'],
                    'ma_kich_co' => $item['ma_kich_co'] ?? null,
                    'so_luong' => $item['so_luong'],
                    'don_gia' => $item['don_gia'],
                    'ghi_chu' => $item['ghi_chu'] ?? null,
                ]);

                if (isset($item['toppings']) && is_array($item['toppings'])) {
                    foreach ($item['toppings'] as $tp) {
                        \App\Models\ChiTietTopping::create([
                            'ma_chi_tiet_topping' => uniqid('CTT_'),
                            'ma_chi_tiet' => $maChiTiet,
                            'ma_topping' => is_array($tp) ? ($tp['ma_topping'] ?? null) : $tp,
                            'so_luong' => is_array($tp) ? ($tp['so_luong'] ?? 1) : 1,
                            'gia_tien' => is_array($tp) ? ($tp['gia_tien'] ?? 0) : 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo đơn hàng thành công',
                'data' => $donHang->load('chiTietDonHangs')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo đơn hàng: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $donHangs = DonHang::with(['chiTietDonHangs.mon', 'ban'])
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách đơn hàng hôm nay thành công',
                'data' => $donHangs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy đơn hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $maDonHang): JsonResponse
    {
        try {
            $request->validate([
                'loai_don' => 'sometimes|required|in:tai_ban,mang_di',
                'phuong_thuc_thanh_toan' => 'sometimes|required|in:tien_mat,chuyen_khoan,momo',
                'trang_thai_thanh_toan' => 'sometimes|required|in:chua_thanh_toan,da_thanh_toan',
            ]);

            DB::beginTransaction();

            $donHang = DonHang::find($maDonHang);

            if (!$donHang) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ], 404);
            }

            $oldStatus = $donHang->trang_thai_don;

            if ($request->has('trang_thai_don')) {
                $donHang->trang_thai_don = $request->input('trang_thai_don');
            }

            if ($request->has('trang_thai_thanh_toan')) {
                $donHang->trang_thai_thanh_toan = $request->input('trang_thai_thanh_toan');
            }

            if ($request->has('loai_don')) {
                $donHang->loai_don = $request->input('loai_don');
            }

            if ($request->has('phuong_thuc_thanh_toan')) {
                $donHang->phuong_thuc_thanh_toan = $request->input('phuong_thuc_thanh_toan');
            }

            $donHang->save();

            $newStatus = $donHang->trang_thai_don;
            $finishedStatuses = ['da_pha_che', 'hoan_thanh'];

            if (in_array($newStatus, $finishedStatuses) && !in_array($oldStatus, $finishedStatuses)) {
                $donHang->load('chiTietDonHangs.mon.congThucs');

                Log::info('DEBUG TRỪ KHO - Số lượng chi tiết đơn:', ['count' => $donHang->chiTietDonHangs->count()]);

                foreach ($donHang->chiTietDonHangs as $chiTiet) {
                    $mon = $chiTiet->mon;
                    if ($mon && $mon->congThucs) {
                        Log::info('DEBUG TRỪ KHO - Món: ' . $mon->ma_mon, ['so_luong_nguyen_lieu' => $mon->congThucs->count()]);
                        foreach ($mon->congThucs as $congThuc) {
                            $tongTru = $congThuc->so_luong_can * $chiTiet->so_luong;

                            Log::info('DEBUG TRỪ KHO - Chuẩn bị trừ:', ['ma_lieu' => $congThuc->ma_nguyen_lieu, 'tru' => $tongTru]);

                            NguyenLieu::where('ma_nguyen_lieu', $congThuc->ma_nguyen_lieu)
                                ->decrement('ton_kho', $tongTru);

                            LichSuKho::create([
                                'ma_ls_kho' => uniqid('LSK_'),
                                'ma_nguyen_lieu' => $congThuc->ma_nguyen_lieu,
                                'ma_nhan_su' => $request->user() ? $request->user()->ma_nhan_su : null,
                                'loai_giao_dich' => 'XUAT_BAN',
                                'so_luong_thay_doi' => -$tongTru
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật đơn hàng thành công',
                'data' => $donHang
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật đơn hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getKdsOrders(Request $request): JsonResponse
    {
        try {
            $orders = DonHang::with(['chiTietDonHangs.mon', 'ban'])
                ->where('trang_thai_don', 'dang_pha')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->get();

            $orders = $orders->map(function (\App\Models\DonHang $order) {
                $minutesWaiting = (int) abs(now()->diffInMinutes($order->created_at));
                $isTakeaway = is_null($order->ma_ban) ? 1 : 0;
                $priorityScore = $minutesWaiting + ($isTakeaway * 5);

                $order->setAttribute('minutes_waiting', $minutesWaiting);
                $order->setAttribute('priority_score', $priorityScore);

                return $order;
            });

            $orders = $orders->sortByDesc('priority_score')->values()->all();

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách KDS: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeQr(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'chi_tiets.*.ghi_chu' => 'nullable|string|max:255',
                'loai_don' => 'required|in:tai_ban,mang_di',
                'phuong_thuc_thanh_toan' => 'required|in:tien_mat,chuyen_khoan,momo',
                'trang_thai_thanh_toan' => 'required|in:chua_thanh_toan,da_thanh_toan',
            ]);

            DB::beginTransaction();

            $chiTiets = $request->input('chi_tiets', []);
            $tongTien = 0;

            foreach ($chiTiets as $item) {
                $tongTien += ($item['so_luong'] * $item['don_gia']);
            }

            $maDonHang = uniqid('DH_');

            // Đơn từ QR luôn đưa vào trạng thái đang pha chế để khớp với flow của quán
            $trangThaiDon = 'dang_pha';

            $donHang = DonHang::create([
                'ma_don_hang' => $maDonHang,
                'ma_ban' => $request->input('ma_ban'),
                'ma_nhan_su' => null, // Không có nhân sự vì là khách tự đặt
                'loai_don' => $request->input('loai_don'),
                'tong_tien' => $tongTien,
                'phuong_thuc_thanh_toan' => $request->input('phuong_thuc_thanh_toan'),
                'trang_thai_thanh_toan' => $request->input('trang_thai_thanh_toan'),
                'trang_thai_don' => $trangThaiDon,
            ]);

            foreach ($chiTiets as $item) {
                $maChiTiet = uniqid('CT_');
                ChiTietDonHang::create([
                    'ma_chi_tiet' => $maChiTiet,
                    'ma_don_hang' => $maDonHang,
                    'ma_mon' => $item['ma_mon'],
                    'ma_kich_co' => $item['ma_kich_co'] ?? null,
                    'so_luong' => $item['so_luong'],
                    'don_gia' => $item['don_gia'],
                    'ghi_chu' => $item['ghi_chu'] ?? null,
                ]);

                if (isset($item['toppings']) && is_array($item['toppings'])) {
                    foreach ($item['toppings'] as $tp) {
                        \App\Models\ChiTietTopping::create([
                            'ma_chi_tiet_topping' => uniqid('CTT_'),
                            'ma_chi_tiet' => $maChiTiet,
                            'ma_topping' => $tp['ma_topping'] ?? $tp,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo đơn hàng từ QR thành công',
                'data' => $donHang->load('chiTietDonHangs')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo đơn hàng QR: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function showQr($maDonHang): JsonResponse
    {
        try {
            $donHang = DonHang::where('ma_don_hang', $maDonHang)->first();
            
            if (!$donHang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'ma_don_hang' => $donHang->ma_don_hang,
                    'trang_thai_don' => $donHang->trang_thai_don,
                    'trang_thai_thanh_toan' => $donHang->trang_thai_thanh_toan,
                    'tong_tien' => $donHang->tong_tien,
                    'phuong_thuc_thanh_toan' => $donHang->phuong_thuc_thanh_toan,
                    'da_danh_gia' => $donHang->danhGia ? true : false,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}
