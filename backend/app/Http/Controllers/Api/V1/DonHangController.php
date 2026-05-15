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
use App\Models\CaLam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\TableUpdated;

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
                'loai_don' => 'required|in:tai_ban,mang_di',
                'phuong_thuc_thanh_toan' => 'required|in:tien_mat,chuyen_khoan',
            ]);

            DB::beginTransaction();

            $chiTiets = $request->input('chi_tiets', []);
            $tongTien = 0;

            foreach ($chiTiets as $item) {
                $mon = Mon::find($item['mon_id']);
                if (!$mon) continue;

                $giaMon = (float) $mon->gia_ban;
                
                // Cộng giá kích cỡ (ưu tiên giá riêng theo món từ pivot)
                if (isset($item['kich_co_id'])) {
                    $giaMon += $this->getSizePrice($mon->id, $item['kich_co_id']);
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

            // Tìm ca làm đang mở của nhân viên hiện tại
            $caLam = CaLam::where('nhan_su_id', $request->user()->id)
                ->where('trang_thai', 1)
                ->first();

            if (!$caLam) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa mở ca làm. Vui lòng mở ca trước khi tạo đơn hàng.'
                ], 403);
            }

            $donHang = DonHang::create([
                'ma_don_hang' => 'DH_' . strtoupper(uniqid()),
                'ban_id' => $ban ? $ban->id : null,
                'nhan_su_id' => $request->user()->id,
                'ca_lam_id' => $caLam ? $caLam->id : null,
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

                // Broadcast trạng thái bàn mới
                try {
                    broadcast(new TableUpdated($ban));
                } catch (\Exception $e) {}
            }

            $orderGhiChu = [];
            foreach ($chiTiets as $item) {
                $mon = Mon::find($item['mon_id']);
                $giaMon = $mon->gia_ban;
                if (isset($item['kich_co_id'])) {
                    $giaMon += $this->getSizePrice($mon->id, $item['kich_co_id']);
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

            // Broadcast event cho KDS/POS real-time
            try {
                broadcast(new OrderCreated($donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping']), 'pos'));
            } catch (\Exception $e) {
                // Không block response nếu broadcast lỗi
            }

            return response()->json(['success' => true, 'data' => $donHang], 201);
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
            ? DonHang::with(['chiTietDonHangs.mon', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping', 'ban', 'danhGia'])->find($id)
            : DonHang::with(['chiTietDonHangs.mon', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping', 'ban', 'danhGia'])->where('ma_don_hang', $id)->first();

        if (!$donHang) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json(['success' => true, 'data' => $donHang]);
    }

    /**
     * Tạo đơn hàng từ QR (Khách hàng)
     * 
     * GIÁ ĐƯỢC TÍNH PHÍA SERVER — không tin tưởng giá từ client.
     * Logic tính giá giống hệt store() (POS).
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

            // === TÍNH GIÁ PHÍA SERVER (chống Price Manipulation) ===
            $tongTien = 0;
            foreach ($chiTiets as $item) {
                $mon = Mon::find($item['mon_id']);
                if (!$mon) continue;

                $giaMon = (float) $mon->gia_ban;

                // Cộng giá kích cỡ (ưu tiên giá riêng theo món từ pivot)
                if (isset($item['kich_co_id']) && $item['kich_co_id']) {
                    $giaMon += $this->getSizePrice($mon->id, $item['kich_co_id']);
                }

                // Cộng giá toppings
                $giaToppings = 0;
                $toppingIds = $item['topping_ids'] ?? [];
                foreach ($toppingIds as $tpId) {
                    $id = is_array($tpId) ? ($tpId['topping_id'] ?? $tpId['id']) : $tpId;
                    $tp = Topping::find($id);
                    if ($tp) $giaToppings += (float) $tp->gia_tien;
                }

                $donGiaThucTe = $giaMon + $giaToppings;
                $tongTien += ($item['so_luong'] * $donGiaThucTe);
            }

            // === TÌM BÀN ===
            $ban = null;
            if ($request->input('ban_id')) {
                $ban = Ban::find($request->input('ban_id'));
            }
            if (!$ban && $request->input('ma_ban')) {
                $ban = Ban::where('ma_ban', $request->input('ma_ban'))->first();
            }

            if ($ban && $ban->trang_thai === 0) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Bàn này đang tạm bảo trì, không thể gọi món.'], 403);
            }

            // Kiểm tra tồn kho
            $inventoryErrors = $this->validateInventory($chiTiets);
            if (!empty($inventoryErrors)) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Không đủ nguyên liệu', 'errors' => $inventoryErrors], 422);
            }

            // Tìm ca làm đang mở (bất kỳ nhân viên nào)
            $caLam = CaLam::where('trang_thai', 1)->first();

            $donHang = DonHang::create([
                'ma_don_hang' => 'DH_' . strtoupper(uniqid()),
                'ban_id' => $ban ? $ban->id : null,
                'ca_lam_id' => $caLam ? $caLam->id : null,
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

                // Broadcast trạng thái bàn mới
                try {
                    broadcast(new TableUpdated($ban));
                } catch (\Exception $e) {}
            }

            // === LƯU CHI TIẾT ĐƠN HÀNG + TOPPING (giống store()) ===
            $orderGhiChu = [];
            foreach ($chiTiets as $item) {
                $mon = Mon::find($item['mon_id']);
                if (!$mon) continue;

                $giaMon = (float) $mon->gia_ban;
                if (isset($item['kich_co_id']) && $item['kich_co_id']) {
                    $giaMon += $this->getSizePrice($mon->id, $item['kich_co_id']);
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

                // Lưu toppings vào chi_tiet_toppings
                $toppingIds = $item['topping_ids'] ?? [];
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

            // Trừ kho ngay lập tức cho đơn QR vì nó nhảy vào pha chế luôn
            $this->deductInventory($donHang);

            DB::commit();
            
            // Dispatch event for real-time notification
            broadcast(new OrderCreated($donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping']), 'qr'));
            
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

            // Broadcast để cập nhật KDS/POS real-time
            try {
                broadcast(new OrderCreated($donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping']), 'pos'));
            } catch (\Exception $e) {}

            // Nếu đơn hàng Hoàn thành (2) VÀ đã Thanh toán (1), hoặc đơn bị Hủy (3) -> Giải phóng bàn
            // Chỉ giải phóng nếu KHÔNG CÒN đơn nào khác đang active trên bàn này
            if (($donHang->trang_thai_don == 2 && $donHang->trang_thai_thanh_toan == 1) || $donHang->trang_thai_don == 3) {
                $this->tryReleaseBan($donHang->ban_id, $donHang->id);
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

            // Broadcast sự kiện hủy đơn
            broadcast(new OrderCancelled($donHang));

            // Giải phóng bàn khi hủy (chỉ nếu không còn đơn active khác)
            $this->tryReleaseBan($donHang->ban_id, $donHang->id);

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

            // Broadcast sự kiện hủy đơn
            broadcast(new OrderCancelled($donHang));

            // Giải phóng bàn khi hủy (chỉ nếu không còn đơn active khác)
            $this->tryReleaseBan($donHang->ban_id, $donHang->id);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Đã hủy đơn hàng thành công.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function deductInventory(DonHang $donHang)
    {
        // Lấy nhan_su_id an toàn: Auth::id() sẽ null cho đơn QR (không xác thực)
        $nhanSuId = Auth::id() ?? $donHang->nhan_su_id;

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
                        'nhan_su_id' => $nhanSuId,
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
                            'nhan_su_id' => $nhanSuId,
                            'loai_giao_dich' => 2, // Xuất
                            'so_luong_thay_doi' => -$qty
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Hoàn kho khi hủy đơn.
     * @deprecated Hiện không được sử dụng vì quy trình hiện tại không hoàn trả nguyên liệu khi hủy đơn
     *             (nguyên liệu đã pha chế, không thể hoàn). Giữ lại để dùng trong tương lai nếu cần.
     */
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

    /**
     * Lấy giá phụ thu kích cỡ theo món.
     * Ưu tiên giá riêng trong pivot mon_kich_co, fallback về giá global trong kich_cos.
     */
    private function getSizePrice(int $monId, int $kichCoId): float
    {
        // Tìm giá riêng theo món trong pivot table
        $pivot = DB::table('mon_kich_co')
            ->where('mon_id', $monId)
            ->where('kich_co_id', $kichCoId)
            ->first();

        if ($pivot && $pivot->gia_cong_them !== null) {
            return (float) $pivot->gia_cong_them;
        }

        // Fallback: dùng giá global từ bảng kich_cos
        $kc = KichCo::find($kichCoId);
        return $kc ? (float) $kc->gia_cong_them : 0;
    }

    /**
     * Giải phóng bàn nếu KHÔNG CÒN đơn hàng active nào khác trên cùng bàn.
     * Tránh race condition khi 1 bàn có nhiều đơn (ví dụ khách QR đặt thêm).
     *
     * @param int|null $banId ID bàn cần kiểm tra
     * @param int $excludeDonHangId ID đơn hàng hiện tại (để loại trừ)
     */
    private function tryReleaseBan(?int $banId, int $excludeDonHangId): void
    {
        if (!$banId) return;

        // Đếm số đơn active (đang pha = 1, hoặc hoàn thành nhưng chưa thanh toán) trên bàn này
        $activeCount = DonHang::where('ban_id', $banId)
            ->where('id', '!=', $excludeDonHangId)
            ->where(function ($q) {
                $q->whereIn('trang_thai_don', [0, 1]) // Chờ xử lý hoặc Đang pha
                  ->orWhere(function ($q2) {
                      $q2->where('trang_thai_don', 2) // Hoàn thành
                         ->where('trang_thai_thanh_toan', 0); // Nhưng chưa thanh toán
                  });
            })
            ->count();

        if ($activeCount === 0) {
            $ban = Ban::find($banId);
            if ($ban) {
                $ban->trang_thai = 1; // Trống
                $ban->save();

                // Broadcast trạng thái bàn mới
                try {
                    broadcast(new TableUpdated($ban));
                } catch (\Exception $e) {}
            }
        }
    }
}
