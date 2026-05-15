<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Events\OrderCreated; 
use App\Events\TableUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    /**
     * Xử lý Webhook từ PayOS
     */
    public function handlePayOS(Request $request)
    {
        $data = $request->all();
        Log::info('PayOS Webhook Received:', $data);

        // 1. Kiểm tra cấu trúc dữ liệu
        if (!isset($data['data']) || !isset($data['data']['description'])) {
            return response()->json(['message' => 'Invalid data'], 400);
        }

        $paymentData = $data['data'];
        $description = $paymentData['description'];
        $amount = $paymentData['amount'];

        // 2. Tìm mã đơn hàng trong nội dung chuyển khoản (Ví dụ: DH_645A1B2C...)
        // Regex tìm chuỗi bắt đầu bằng DH_ theo sau là các ký tự chữ cái/số
        preg_match('/DH_[A-Z0-9]+/', strtoupper($description), $matches);
        
        if (empty($matches)) {
            Log::warning('No Order ID found in description: ' . $description);
            return response()->json(['message' => 'Order ID not found in description'], 200); // Trả về 200 để PayOS không gửi lại
        }

        $maDonHang = $matches[0];

        // 3. Tìm đơn hàng trong DB
        $donHang = DonHang::where('ma_don_hang', $maDonHang)->first();

        if (!$donHang) {
            Log::warning('Order not found: ' . $maDonHang);
            return response()->json(['message' => 'Order not found'], 200);
        }

        // 4. Kiểm tra trạng thái và số tiền (Tùy chọn, PayOS đã check số tiền nếu tạo Payment Link)
        // Nếu bạn chỉ dùng QR tĩnh, bạn nên check $amount == $donHang->tong_tien
        if ($donHang->trang_thai_thanh_toan == 1) {
            return response()->json(['message' => 'Order already paid'], 200);
        }

        // 5. Cập nhật đơn hàng
        try {
            DB::beginTransaction();
            
            $donHang->trang_thai_thanh_toan = 1; // Đã thanh toán
            $donHang->trang_thai_don = 2; // Hoàn thành
            $donHang->save();

            // Nếu là đơn tại bàn, giải phóng bàn (Copy logic từ DonHangController)
            if ($donHang->ban_id) {
                $this->tryReleaseBan($donHang->ban_id, $donHang->id);
            }

            DB::commit();

            // 6. Broadcast real-time cho POS/KDS
            try {
                // Sử dụng lại OrderCreated hoặc tạo OrderUpdated mới
                broadcast(new OrderCreated($donHang->load(['chiTietDonHangs.mon', 'ban', 'chiTietDonHangs.kichCo', 'chiTietDonHangs.chiTietToppings.topping'])));
            } catch (\Exception $e) {
                Log::error('Broadcast error in Webhook: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'message' => 'Order updated successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating order via Webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Logic giải phóng bàn (Copy từ DonHangController để tránh dependency phức tạp)
     */
    private function tryReleaseBan(?int $banId, int $excludeDonHangId): void
    {
        if (!$banId) return;

        $activeCount = DonHang::where('ban_id', $banId)
            ->where('id', '!=', $excludeDonHangId)
            ->where(function ($q) {
                $q->whereIn('trang_thai_don', [0, 1])
                  ->orWhere(function ($q2) {
                      $q2->where('trang_thai_don', 2)
                         ->where('trang_thai_thanh_toan', 0);
                  });
            })
            ->count();

        if ($activeCount === 0) {
            $ban = \App\Models\Ban::find($banId);
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
