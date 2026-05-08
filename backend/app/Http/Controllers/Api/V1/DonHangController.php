<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use App\Models\NguyenLieu;
use App\Models\LichSuKho;
use App\Models\Mon;
use App\Models\Ban;
use App\Models\KichCo;
use App\Models\Topping;
use App\Models\ChiTietTopping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\OrderCreated;

class DonHangController extends Controller
{
    /**
     * Danh sách đơn hàng trong ngày
     */
    public function index(Request $request): JsonResponse
    {
        $query = DonHang::with(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.chiTietToppings.topping', 'chiTietDonHangs.kichCo'])
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('created_at', 'desc');

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Danh sách đơn hàng cho KDS (Đang pha)
     */
    public function getKdsOrders(): JsonResponse
    {
        $orders = DonHang::with(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping'])
            ->where('trang_thai_don', 1) // Đang pha
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Tạo đơn hàng từ POS (Nhân viên)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'chi_tiets' => 'required|array',
                'loai_don' => 'required',
                'phuong_thuc_thanh_toan' => 'required',
            ]);

            DB::beginTransaction();

            $chiTiets = $request->input('chi_tiets', []);
            $tongTien = 0;

            foreach ($chiTiets as $item) {
                $mon = Mon::find($item['mon_id']);
                if (!$mon) continue;

                $giaMon = (float) $mon->gia_ban;
                
                // Cộng giá kích cỡ
                if (isset($item['kich_co_id'])) {
                    $kc = KichCo::find($item['kich_co_id']);
                    if ($kc) $giaMon += (float) $kc->gia_cong_them;
                }

                // Cộng giá toppings
                $giaToppings = 0;
                $toppingIds = $item['topping_ids'] ?? $item['toppings'] ?? [];
                foreach ($toppingIds as $tpId) {
                    $id = is_array($tpId) ? ($tpId['topping_id'] ?? $tpId['id']) : $tpId;
                    $tp = Topping::find($id);
                    if ($tp) $giaToppings += (float) $tp->gia_tien;
                }

                $donGiaThucTe = $giaMon + $giaToppings;
                $tongTien += ($item['so_luong'] * $donGiaThucTe);
            }

            // Kiểm tra tồn kho
            $inventoryErrors = $this->validateInventory($chiTiets);
            if (!empty($inventoryErrors)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Không đủ nguyên liệu', 'errors' => $inventoryErrors], 422);
            }

            $ban = $request->input('ban_id') ? Ban::find($request->input('ban_id')) : null;

            $donHang = DonHang::create([
                'ma_don_hang' => 'DH_' . strtoupper(uniqid()),
                'ban_id' => $ban ? $ban->id : null,
                'nhan_su_id' => $request->user()->id,
                'loai_don' => $request->input('loai_don'),
                'tong_tien' => $tongTien,
                'phuong_thuc_thanh_toan' => $request->input('phuong_thuc_thanh_toan'),
                'trang_thai_thanh_toan' => $request->input('trang_thai_thanh_toan') == 1 ? 1 : 0,
                'trang_thai_don' => $request->input('trang_thai_don') ?? 1, 
            ]);

            // Cập nhật trạng thái bàn sang "Có khách" (2)
            if ($ban) {
                $ban->trang_thai = 2;
                $ban->save();
            }

            $orderGhiChu = [];
            foreach ($chiTiets as $item) {
                $mon = Mon::find($item['mon_id']);
                $giaMon = $mon->gia_ban;
                if (isset($item['kich_co_id'])) {
                    $kc = KichCo::find($item['kich_co_id']);
                    if ($kc) $giaMon += $kc->gia_cong_them;
                }

                $chiTietDonHang = ChiTietDonHang::create([
                    'ma_chi_tiet' => 'CT_' . strtoupper(uniqid()),
                    'don_hang_id' => $donHang->id,
                    'mon_id' => $item['mon_id'],
                    'kich_co_id' => $item['kich_co_id'] ?? null,
                    'so_luong' => $item['so_luong'],
                    'don_gia' => $giaMon,
                    'ghi_chu' => $item['ghi_chu'] ?? null,
                ]);

                if (!empty($item['ghi_chu'])) {
                    $orderGhiChu[] = $item['ghi_chu'];
                }

                $toppingIds = $item['topping_ids'] ?? $item['toppings'] ?? [];
                foreach ($toppingIds as $tpId) {
                    $id = is_array($tpId) ? ($tpId['topping_id'] ?? $tpId['id']) : $tpId;
                    $tp = Topping::find($id);
                    if ($tp) {
                        ChiTietTopping::create([
                            'ma_chi_tiet_topping' => 'CTT_' . strtoupper(uniqid()),
                            'chi_tiet_don_hang_id' => $chiTietDonHang->id,
                            'topping_id' => $tp->id,
                            'so_luong' => 1,
                            'gia_tien' => $tp->gia_tien,
                        ]);
                    }
                }
            }

            if (!empty($orderGhiChu)) {
                $donHang->ghi_chu = implode('; ', $orderGhiChu);
                $donHang->save();
            }

            // Trừ kho ngay nếu đơn hàng bắt đầu pha (1) hoặc hoàn thành (2)
            if ($donHang->trang_thai_don == 1 || $donHang->trang_thai_don == 2) {
                $this->deductInventory($donHang);
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Lấy trạng thái đơn hàng QR (Cho khách hàng)
     * @param string|int $id
     */
    public function showQr($id): JsonResponse
    {
        $donHang = is_numeric($id) 
            ? DonHang::with(['chiTietDonHangs.mon', 'ban', 'danhGia'])->find($id)
            : DonHang::with(['chiTietDonHangs.mon', 'ban', 'danhGia'])->where('ma_don_hang', $id)->first();

        if (!$donHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json(['success' => true, 'data' => $donHang]);
    }

    /**
     * Tạo đơn hàng từ QR (Khách hàng)
     */
    public function storeQr(Request $request): JsonResponse
    {
        try {
            $hasActiveShift = \App\Models\CaLam::where('trang_thai', 1)->exists();
            if (!$hasActiveShift) {
                return response()->json(['success' => false, 'message' => 'Cửa hàng đang tạm nghỉ.'], 403);
            }

            DB::beginTransaction();
            $chiTiets = $request->input('chi_tiets', []);
            $tongTien = 0;

            foreach ($chiTiets as $item) {
                $tongTien += ($item['so_luong'] * $item['don_gia']);
            }

            $ban = Ban::where('id', $request->input('ban_id'))
                      ->orWhere('ma_ban', $request->input('ma_ban'))->first();

            // Kiểm tra tồn kho
            $inventoryErrors = $this->validateInventory($chiTiets);
            if (!empty($inventoryErrors)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Không đủ nguyên liệu', 'errors' => $inventoryErrors], 422);
            }

            $donHang = DonHang::create([
                'ma_don_hang' => 'DH_' . uniqid(),
                'ban_id' => $ban ? $ban->id : null,
                'loai_don' => 'tai_ban',
                'tong_tien' => $tongTien,
                'phuong_thuc_thanh_toan' => $request->input('phuong_thuc_thanh_toan', 'tien_mat'),
                'trang_thai_thanh_toan' => 0,
                'trang_thai_don' => 1, // Nhảy vào pha chế luôn (Đang pha)
            ]);

            // Cập nhật trạng thái bàn sang "Có khách" (2)
            if ($ban) {
                $ban->trang_thai = 2;
                $ban->save();
            }

            $orderGhiChu = [];
            foreach ($chiTiets as $item) {
                ChiTietDonHang::create([
                    'ma_chi_tiet' => 'CT_' . uniqid(),
                    'don_hang_id' => $donHang->id,
                    'mon_id' => $item['mon_id'],
                    'so_luong' => $item['so_luong'],
                    'don_gia' => $item['don_gia'],
                    'ghi_chu' => $item['ghi_chu'] ?? null,
                ]);
                if (!empty($item['ghi_chu'])) {
                    $orderGhiChu[] = $item['ghi_chu'];
                }
            }

            if (!empty($orderGhiChu)) {
                $donHang->ghi_chu = implode('; ', $orderGhiChu);
                $donHang->save();
            }

            // Trừ kho ngay lập tức cho đơn QR vì nó nhảy vào pha chế luôn
            $this->deductInventory($donHang);

            DB::commit();
            
            // Dispatch event for real-time notification
            broadcast(new OrderCreated($donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping'])));
            
            return response()->json(['success' => true, 'data' => $donHang], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cập nhật trạng thái đơn (POS)
     * @param string|int $id
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $donHang = is_numeric($id) 
                ? DonHang::lockForUpdate()->find($id)
                : DonHang::lockForUpdate()->where('ma_don_hang', $id)->first();

            if (!$donHang) return response()->json(['success' => false, 'message' => 'Không thấy đơn'], 404);

            $oldStatus = (int)$donHang->trang_thai_don;
            
            if ($request->has('trang_thai_don')) {
                $newStatus = (int)$request->input('trang_thai_don');
                
                // Logic trừ kho: Nếu từ Chờ xác nhận (0) chuyển sang Đang pha (1) hoặc Hoàn thành (2)
                if (($newStatus == 1 || $newStatus == 2) && $oldStatus == 0) {
                    // Kiểm tra tồn kho trước khi chuyển trạng thái
                    $inventoryErrors = $this->validateInventory($donHang);
                    if (!empty($inventoryErrors)) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'Không đủ nguyên liệu để chuẩn bị đơn', 'errors' => $inventoryErrors], 422);
                    }
                    $this->deductInventory($donHang);
                }

                $donHang->trang_thai_don = $newStatus;
            }

            if ($request->has('trang_thai_thanh_toan')) {
                $donHang->trang_thai_thanh_toan = $request->input('trang_thai_thanh_toan');
            }

            if ($request->has('ly_do_huy')) {
                $donHang->ly_do_huy = $request->input('ly_do_huy');
            }

            // Luôn cập nhật nhân viên xử lý cuối cùng để ghi nhận vào ca làm (khi thanh toán hoặc hoàn thành)
            if (Auth::check()) {
                $donHang->nhan_su_id = Auth::id();
            }

            $donHang->save();

            // Nếu đơn hàng Hoàn thành (2) VÀ đã Thanh toán (1), hoặc đơn bị Hủy (3) -> Giải phóng bàn
            if (($donHang->trang_thai_don == 2 && $donHang->trang_thai_thanh_toan == 1) || $donHang->trang_thai_don == 3) {
                if ($donHang->ban_id) {
                    $ban = Ban::find($donHang->ban_id);
                    if ($ban) {
                        $ban->trang_thai = 1; // Trống
                        $ban->save();
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping'])]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hủy đơn hàng (POS)
     * @param string|int $id
     */
    public function cancelOrder(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $donHang = is_numeric($id) 
                ? DonHang::lockForUpdate()->find($id)
                : DonHang::lockForUpdate()->where('ma_don_hang', $id)->first();

            if (!$donHang) return response()->json(['success' => false, 'message' => 'Không thấy đơn'], 404);

            $donHang->trang_thai_don = 3; // Đã hủy
            $donHang->ly_do_huy = $request->input('ly_do_huy', 'Nhân viên hủy đơn');
            $donHang->save();

            // Giải phóng bàn khi hủy
            if ($donHang->ban_id) {
                $ban = Ban::find($donHang->ban_id);
                if ($ban) {
                    $ban->trang_thai = 1; // Trống
                    $ban->save();
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã hủy đơn hàng. Nguyên liệu không được hoàn trả vì đã được pha chế.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Hủy đơn hàng QR (Khách hàng)
     * @param string|int $id
     */
    public function cancelOrderQr(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();
            $donHang = is_numeric($id) 
                ? DonHang::lockForUpdate()->find($id)
                : DonHang::lockForUpdate()->where('ma_don_hang', $id)->first();

            if (!$donHang) return response()->json(['success' => false, 'message' => 'Không thấy đơn'], 404);

            if ($donHang->trang_thai_don != 0) {
                return response()->json(['success' => false, 'message' => 'Đơn hàng đã được đưa vào pha chế, khách hàng không thể tự hủy.'], 400);
            }

            $donHang->trang_thai_don = 3; // Đã hủy
            $donHang->ly_do_huy = $request->input('ly_do_huy', 'Khách hàng tự hủy');
            $donHang->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã hủy đơn hàng thành công.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function deductInventory(DonHang $donHang)
    {
        $donHang->load(['chiTietDonHangs.mon.congThucs', 'chiTietDonHangs.chiTietToppings.topping.congThucs']);
        foreach ($donHang->chiTietDonHangs as $chiTiet) {
            // Trừ kho món chính
            if ($chiTiet->mon && $chiTiet->mon->congThucs) {
                foreach ($chiTiet->mon->congThucs as $ct) {
                    $qty = $ct->so_luong_can * $chiTiet->so_luong;
                    NguyenLieu::where('id', $ct->nguyen_lieu_id)->decrement('ton_kho', $qty);
                    LichSuKho::create([
                        'ma_ls_kho' => 'LSK_' . uniqid(),
                        'nguyen_lieu_id' => $ct->nguyen_lieu_id,
                        'nhan_su_id' => Auth::id(),
                        'loai_giao_dich' => 2, // Xuất
                        'so_luong_thay_doi' => -$qty
                    ]);
                }
            }

            // Trừ kho toppings
            foreach ($chiTiet->chiTietToppings as $ctt) {
                if ($ctt->topping && $ctt->topping->congThucs) {
                    foreach ($ctt->topping->congThucs as $ct) {
                        // Topping cũng nhân với số lượng của món chính
                        $qty = $ct->so_luong_can * $chiTiet->so_luong;
                        NguyenLieu::where('id', $ct->nguyen_lieu_id)->decrement('ton_kho', $qty);
                        LichSuKho::create([
                            'ma_ls_kho' => 'LSK_' . uniqid(),
                            'nguyen_lieu_id' => $ct->nguyen_lieu_id,
                            'nhan_su_id' => Auth::id(),
                            'loai_giao_dich' => 2, // Xuất
                            'so_luong_thay_doi' => -$qty
                        ]);
                    }
                }
            }
        }
    }

    private function restoreInventory(DonHang $donHang)
    {
        $donHang->load(['chiTietDonHangs.mon.congThucs', 'chiTietDonHangs.chiTietToppings.topping.congThucs']);
        foreach ($donHang->chiTietDonHangs as $chiTiet) {
            // Hoàn kho món chính
            if ($chiTiet->mon && $chiTiet->mon->congThucs) {
                foreach ($chiTiet->mon->congThucs as $ct) {
                    $qty = $ct->so_luong_can * $chiTiet->so_luong;
                    NguyenLieu::where('id', $ct->nguyen_lieu_id)->increment('ton_kho', $qty);
                    LichSuKho::create([
                        'ma_ls_kho' => 'LSK_' . uniqid(),
                        'nguyen_lieu_id' => $ct->nguyen_lieu_id,
                        'nhan_su_id' => Auth::id(),
                        'loai_giao_dich' => 1, // Nhập (Hoàn lại)
                        'so_luong_thay_doi' => $qty
                    ]);
                }
            }

            // Hoàn kho toppings
            foreach ($chiTiet->chiTietToppings as $ctt) {
                if ($ctt->topping && $ctt->topping->congThucs) {
                    foreach ($ctt->topping->congThucs as $ct) {
                        $qty = $ct->so_luong_can * $chiTiet->so_luong;
                        NguyenLieu::where('id', $ct->nguyen_lieu_id)->increment('ton_kho', $qty);
                        LichSuKho::create([
                            'ma_ls_kho' => 'LSK_' . uniqid(),
                            'nguyen_lieu_id' => $ct->nguyen_lieu_id,
                            'nhan_su_id' => Auth::id(),
                            'loai_giao_dich' => 1, // Nhập (Hoàn lại)
                            'so_luong_thay_doi' => $qty
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Kiểm tra tồn kho
     * @param array|DonHang $items
     */
    private function validateInventory($items): array
    {
        $errors = [];
        $required = [];

        if ($items instanceof DonHang) {
            $items->load(['chiTietDonHangs.mon.congThucs', 'chiTietDonHangs.chiTietToppings.topping.congThucs']);
            foreach ($items->chiTietDonHangs as $chiTiet) {
                // Yêu cầu cho món chính
                if ($chiTiet->mon) {
                    foreach ($chiTiet->mon->congThucs as $ct) {
                        $required[$ct->nguyen_lieu_id] = ($required[$ct->nguyen_lieu_id] ?? 0) + ($ct->so_luong_can * $chiTiet->so_luong);
                    }
                }
                // Yêu cầu cho toppings
                foreach ($chiTiet->chiTietToppings as $ctt) {
                    if ($ctt->topping) {
                        foreach ($ctt->topping->congThucs as $ct) {
                            $required[$ct->nguyen_lieu_id] = ($required[$ct->nguyen_lieu_id] ?? 0) + ($ct->so_luong_can * $chiTiet->so_luong);
                        }
                    }
                }
            }
        } else {
            foreach ($items as $item) {
                // Yêu cầu cho món chính
                $mon = Mon::with('congThucs')->find($item['mon_id']);
                if ($mon) {
                    foreach ($mon->congThucs as $ct) {
                        $required[$ct->nguyen_lieu_id] = ($required[$ct->nguyen_lieu_id] ?? 0) + ($ct->so_luong_can * $item['so_luong']);
                    }
                }

                // Yêu cầu cho toppings
                $toppingIds = $item['topping_ids'] ?? $item['toppings'] ?? [];
                foreach ($toppingIds as $tpId) {
                    $id = is_array($tpId) ? ($tpId['topping_id'] ?? $tpId['id']) : $tpId;
                    $tp = Topping::with('congThucs')->find($id);
                    if ($tp) {
                        foreach ($tp->congThucs as $ct) {
                            $required[$ct->nguyen_lieu_id] = ($required[$ct->nguyen_lieu_id] ?? 0) + ($ct->so_luong_can * $item['so_luong']);
                        }
                    }
                }
            }
        }

        foreach ($required as $id => $needed) {
            $nl = NguyenLieu::find($id);
            if (!$nl || $nl->ton_kho < $needed) {
                $errors[] = [
                    'nguyen_lieu' => $nl ? $nl->ten_nguyen_lieu : 'N/A', 
                    'ton_kho' => $nl ? (float)$nl->ton_kho : 0, 
                    'can' => (float)$needed
                ];
            }
        }
        return $errors;
    }
}
