<?php

namespace App\Services;

use App\Models\DonHang;
use App\Models\NguyenLieu;
use App\Models\ChiTietDonHang;
use App\Models\DanhGia;
use App\Models\Mon;
use App\Models\AIChatHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;
    private string $model = 'gemini-2.0-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
    }

    /**
     * Gửi tin nhắn tới AI và trả về phản hồi
     */
    public function chat(string $message, string $sessionId, string $maNhanSu): string
    {
        try {
            // 1. Lấy lịch sử chat gần đây (tối đa 10 tin nhắn)
            $history = AIChatHistory::where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->values();

            // 2. Xây dựng context từ database
            $context = $this->buildContext();

            // 3. Xây dựng system prompt
            $systemPrompt = $this->buildSystemPrompt($context);

            // 4. Chuẩn bị nội dung gửi cho Gemini
            $contents = [];

            // System instruction qua system_instruction field
            // Lịch sử chat
            foreach ($history as $msg) {
                $contents[] = [
                    'role' => $msg->role === 'user' ? 'user' : 'model',
                    'parts' => [['text' => $msg->content]]
                ];
            }

            // Tin nhắn hiện tại
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]]
            ];

            // 5. Gọi Gemini API (với retry và fallback model)
            $models = ['gemini-2.0-flash', 'gemini-1.5-flash'];
            $reply = null;

            foreach ($models as $modelName) {
                $response = Http::timeout(30)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$this->apiKey}",
                    [
                        'system_instruction' => [
                            'parts' => [['text' => $systemPrompt]]
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'topP' => 0.9,
                            'maxOutputTokens' => 2048,
                        ]
                    ]
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    break;
                }

                // Nếu bị rate limit (429), thử model tiếp theo
                if ($response->status() === 429) {
                    Log::warning("Gemini rate limit on {$modelName}, trying next model...");
                    sleep(1);
                    continue;
                }

                // Lỗi khác
                Log::error('Gemini API error', [
                    'model' => $modelName,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

            if (!$reply) {
                return 'Xin lỗi, tôi đang gặp sự cố kết nối. Vui lòng thử lại sau ít phút. ⏳';
            }

            // 6. Lưu cả tin nhắn user và reply vào lịch sử
            AIChatHistory::create([
                'ma_nhan_su' => $maNhanSu,
                'session_id' => $sessionId,
                'role' => 'user',
                'content' => $message,
            ]);

            AIChatHistory::create([
                'ma_nhan_su' => $maNhanSu,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return $reply;

        } catch (\Exception $e) {
            Log::error('AIService error: ' . $e->getMessage());
            return 'Xin lỗi, đã xảy ra lỗi khi xử lý câu hỏi của bạn. Vui lòng thử lại.';
        }
    }

    /**
     * Xây dựng bối cảnh dữ liệu thực từ database
     */
    private function buildContext(): string
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekAgo = Carbon::today()->subDays(7);

        // --- Doanh thu ---
        $doanhThuHomNay = DonHang::whereDate('created_at', $today)
            ->where('trang_thai_thanh_toan', 'da_thanh_toan')
            ->sum('tong_tien');

        $tongDonHomNay = DonHang::whereDate('created_at', $today)->count();

        $doanhThuHomQua = DonHang::whereDate('created_at', $yesterday)
            ->where('trang_thai_thanh_toan', 'da_thanh_toan')
            ->sum('tong_tien');

        $tongDonHomQua = DonHang::whereDate('created_at', $yesterday)->count();

        // Doanh thu 7 ngày
        $doanhThu7Ngay = DonHang::whereDate('created_at', '>=', $weekAgo)
            ->where('trang_thai_thanh_toan', 'da_thanh_toan')
            ->sum('tong_tien');

        $tongDon7Ngay = DonHang::whereDate('created_at', '>=', $weekAgo)->count();

        // Doanh thu theo từng ngày trong tuần
        $doanhThuTungNgay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $rev = DonHang::whereDate('created_at', $date)
                ->where('trang_thai_thanh_toan', 'da_thanh_toan')
                ->sum('tong_tien');
            $orders = DonHang::whereDate('created_at', $date)->count();
            $doanhThuTungNgay[] = $date->format('d/m') . ": " . number_format($rev) . "đ ({$orders} đơn)";
        }

        // --- Đơn hàng đang xử lý ---
        $donDangPha = DonHang::where('trang_thai_don', 'dang_pha')
            ->whereDate('created_at', $today)->count();

        $donChoXacNhan = DonHang::where('trang_thai_don', 'cho_xac_nhan')
            ->whereDate('created_at', $today)->count();

        // --- Top món bán chạy (7 ngày) ---
        $topMon = ChiTietDonHang::select('ma_mon', DB::raw('SUM(so_luong) as tong_ban'), DB::raw('SUM(so_luong * don_gia) as tong_doanh_thu'))
            ->whereHas('donHang', function ($q) use ($weekAgo) {
                $q->whereDate('created_at', '>=', $weekAgo)
                  ->where('trang_thai_thanh_toan', 'da_thanh_toan');
            })
            ->groupBy('ma_mon')
            ->orderByDesc('tong_ban')
            ->limit(10)
            ->with('mon:ma_mon,ten_mon,gia_ban')
            ->get()
            ->map(function ($item) {
                $tenMon = $item->mon ? $item->mon->ten_mon : 'N/A';
                $giaBan = $item->mon ? number_format($item->mon->gia_ban) : '0';
                return "- {$tenMon}: {$item->tong_ban} ly, doanh thu " . number_format($item->tong_doanh_thu) . "đ (giá bán: {$giaBan}đ)";
            });

        // Món bán ít nhất
        $monBanIt = ChiTietDonHang::select('ma_mon', DB::raw('SUM(so_luong) as tong_ban'))
            ->whereHas('donHang', function ($q) use ($weekAgo) {
                $q->whereDate('created_at', '>=', $weekAgo);
            })
            ->groupBy('ma_mon')
            ->orderBy('tong_ban')
            ->limit(5)
            ->with('mon:ma_mon,ten_mon,gia_ban')
            ->get()
            ->map(function ($item) {
                $tenMon = $item->mon ? $item->mon->ten_mon : 'N/A';
                return "- {$tenMon}: chỉ {$item->tong_ban} ly";
            });

        // --- Tồn kho ---
        $nguyenLieuCanhBao = NguyenLieu::where('trang_thai', 'hoat_dong')
            ->where(function ($q) {
                $q->where('ton_kho', '<=', 0)
                  ->orWhereColumn('ton_kho', '<=', 'muc_canh_bao');
            })
            ->select('ten_nguyen_lieu', 'don_vi_tinh', 'ton_kho', 'muc_canh_bao')
            ->orderBy('ton_kho')
            ->get()
            ->map(function ($nl) {
                $status = $nl->ton_kho <= 0 ? '🔴 HẾT HÀNG' : '🟡 SẮP HẾT';
                return "- {$nl->ten_nguyen_lieu}: còn {$nl->ton_kho} {$nl->don_vi_tinh} (mức cảnh báo: {$nl->muc_canh_bao}) {$status}";
            });

        // Tính tốc độ tiêu thụ nguyên liệu (dự đoán)
        $tieuThuDuDoan = $this->calculateConsumptionRate();

        // --- Đánh giá ---
        $danhGiaTB = DanhGia::avg('so_sao');
        $tongDanhGia = DanhGia::count();
        $danhGiaGanDay = DanhGia::orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($dg) {
                $stars = str_repeat('⭐', $dg->so_sao);
                $comment = $dg->noi_dung ? " - \"{$dg->noi_dung}\"" : '';
                return "- {$stars}{$comment}";
            });

        // --- Tổng hợp menu ---
        $tongMon = Mon::where('trang_thai', 'dang_ban')->count();
        $tongMonNgung = Mon::where('trang_thai', 'ngung_ban')->count();

        // --- Build context string ---
        $context = "📊 DỮ LIỆU KINH DOANH SMART CAFE (Cập nhật: " . now()->format('H:i d/m/Y') . ")\n\n";

        $context .= "═══ DOANH THU ═══\n";
        $context .= "Hôm nay: " . number_format($doanhThuHomNay) . "đ ({$tongDonHomNay} đơn)\n";
        $context .= "Hôm qua: " . number_format($doanhThuHomQua) . "đ ({$tongDonHomQua} đơn)\n";
        $context .= "7 ngày qua: " . number_format($doanhThu7Ngay) . "đ ({$tongDon7Ngay} đơn)\n";
        if ($doanhThuHomQua > 0) {
            $pctChange = round(($doanhThuHomNay - $doanhThuHomQua) / $doanhThuHomQua * 100, 1);
            $trend = $pctChange >= 0 ? "📈 +{$pctChange}%" : "📉 {$pctChange}%";
            $context .= "So với hôm qua: {$trend}\n";
        }
        $context .= "\nDoanh thu chi tiết 7 ngày:\n" . implode("\n", $doanhThuTungNgay) . "\n";

        $context .= "\n═══ ĐƠN HÀNG HIỆN TẠI ═══\n";
        $context .= "Đang pha chế: {$donDangPha} đơn\n";
        $context .= "Chờ xác nhận: {$donChoXacNhan} đơn\n";

        $context .= "\n═══ TOP MÓN BÁN CHẠY (7 ngày) ═══\n";
        $context .= $topMon->implode("\n") . "\n";

        if ($monBanIt->isNotEmpty()) {
            $context .= "\n═══ MÓN BÁN ÍT (7 ngày) ═══\n";
            $context .= $monBanIt->implode("\n") . "\n";
        }

        $context .= "\n═══ THỰC ĐƠN ═══\n";
        $context .= "Tổng món đang bán: {$tongMon} | Ngừng bán: {$tongMonNgung}\n";

        $context .= "\n═══ TỒN KHO & CẢNH BÁO ═══\n";
        if ($nguyenLieuCanhBao->isEmpty()) {
            $context .= "✅ Tất cả nguyên liệu đều đủ.\n";
        } else {
            $context .= $nguyenLieuCanhBao->implode("\n") . "\n";
        }

        if (!empty($tieuThuDuDoan)) {
            $context .= "\n═══ DỰ ĐOÁN TIÊU THỤ ═══\n";
            $context .= $tieuThuDuDoan . "\n";
        }

        $context .= "\n═══ ĐÁNH GIÁ KHÁCH HÀNG ═══\n";
        $context .= "Trung bình: " . ($danhGiaTB ? round($danhGiaTB, 1) . "/5 ⭐" : "Chưa có") . " ({$tongDanhGia} đánh giá)\n";
        if ($danhGiaGanDay->isNotEmpty()) {
            $context .= "Đánh giá gần đây:\n" . $danhGiaGanDay->implode("\n") . "\n";
        }

        return $context;
    }

    /**
     * Tính tốc độ tiêu thụ nguyên liệu và dự đoán ngày hết
     */
    private function calculateConsumptionRate(): string
    {
        $weekAgo = Carbon::today()->subDays(7);

        // Lấy nguyên liệu đang hoạt động có lịch sử xuất kho
        $materials = NguyenLieu::where('trang_thai', 'hoat_dong')
            ->whereColumn('ton_kho', '<=', DB::raw('muc_canh_bao * 2'))
            ->where('ton_kho', '>', 0)
            ->get();

        if ($materials->isEmpty()) return '';

        $predictions = [];
        foreach ($materials as $nl) {
            // Tính tổng tiêu thụ 7 ngày qua từ lịch sử kho (xuất bán)
            $totalConsumed = abs(
                DB::table('lich_su_khos')
                    ->where('ma_nguyen_lieu', $nl->ma_nguyen_lieu)
                    ->where('loai_giao_dich', 'XUAT_BAN')
                    ->whereDate('created_at', '>=', $weekAgo)
                    ->sum('so_luong_thay_doi')
            );

            if ($totalConsumed > 0) {
                $dailyRate = $totalConsumed / 7;
                $daysLeft = $dailyRate > 0 ? round($nl->ton_kho / $dailyRate, 1) : 999;

                if ($daysLeft <= 7) {
                    $urgency = $daysLeft <= 2 ? '🔴 KHẨN CẤP' : ($daysLeft <= 4 ? '🟠 CẦN CHÚ Ý' : '🟡');
                    $predictions[] = "- {$nl->ten_nguyen_lieu}: tiêu thụ ~" . round($dailyRate, 1) . " {$nl->don_vi_tinh}/ngày → còn ~{$daysLeft} ngày {$urgency}";
                }
            }
        }

        return empty($predictions) ? '' : implode("\n", $predictions);
    }

    /**
     * Xây dựng System Prompt
     */
    private function buildSystemPrompt(string $context): string
    {
        return <<<PROMPT
Bạn là **AI Trợ lý Quản lý** của quán **Smart Cafe**. Bạn tên là "Cafe AI". Vai trò của bạn là một nhà phân tích kinh doanh thông minh, hỗ trợ người quản lý vận hành quán cafe hiệu quả.

## QUY TẮC QUAN TRỌNG
1. Luôn trả lời bằng **tiếng Việt**, ngắn gọn, chuyên nghiệp nhưng thân thiện.
2. Trả lời dựa trên **DỮ LIỆU THỰC** được cung cấp bên dưới. KHÔNG bịa số liệu.
3. Khi đưa ra số tiền, luôn format: 1,500,000đ (có dấu phẩy ngăn cách).
4. Sử dụng emoji phù hợp để tăng tính trực quan (📊 💰 📈 📉 ⚠️ ✅ 🔥).
5. Khi phân tích, luôn kèm **gợi ý hành động cụ thể** cho quản lý.
6. Bạn CHỈ ĐỌC và PHÂN TÍCH — KHÔNG thực hiện bất kỳ hành động nào trên hệ thống.

## KHẢ NĂNG CỦA BẠN

### 📦 Quản lý Kho & Dự đoán (Predictive Inventory)
- Cảnh báo nguyên liệu sắp hết với thời gian dự đoán cụ thể
- Gợi ý danh sách nhập hàng dựa trên tốc độ tiêu thụ
- Phân tích xu hướng tiêu thụ nguyên liệu

### 💬 Truy vấn Dữ liệu Tức thì (Conversational BI)
- Trả lời mọi câu hỏi về doanh thu, đơn hàng, kho bằng ngôn ngữ tự nhiên
- So sánh dữ liệu giữa các khoảng thời gian
- Tóm tắt tình hình kinh doanh nhanh chóng

### 📊 Phân tích Kinh doanh & Menu Engineering
- Phân loại món theo ma trận Menu Engineering:
  * ⭐ **Ngôi sao** (Stars): Bán chạy + Lợi nhuận cao → Giữ nguyên, quảng bá mạnh
  * 🐄 **Bò sữa** (Plowhorses): Bán chạy + Lợi nhuận thấp → Tăng giá nhẹ hoặc giảm chi phí
  * 🧩 **Đố đánh** (Puzzles): Bán chậm + Lợi nhuận cao → Tăng marketing, combo
  * 🐕 **Chó mực** (Dogs): Bán chậm + Lợi nhuận thấp → Cân nhắc bỏ hoặc đổi mới
- Đề xuất combo, khuyến mãi dựa trên dữ liệu
- Phát hiện điểm bất thường (doanh thu tăng/giảm đột biến)
- Báo cáo tóm tắt kinh doanh

## Khi trả lời:
- Nếu câu hỏi về **số liệu cụ thể**: Trích dẫn trực tiếp từ dữ liệu
- Nếu câu hỏi về **gợi ý/tư vấn**: Phân tích dữ liệu + đưa ra khuyến nghị có cơ sở
- Nếu câu hỏi về **dự đoán**: Dựa trên trend từ dữ liệu, nêu rõ đây là ước tính
- Nếu câu hỏi **không liên quan** đến quán cafe: Nhẹ nhàng từ chối, hướng lại chủ đề
- Sử dụng **bảng Markdown** khi so sánh nhiều mục
- Sử dụng **danh sách** khi liệt kê

═══════════════════════════════════
{$context}
═══════════════════════════════════
PROMPT;
    }

    /**
     * Lấy lịch sử chat của session
     */
    public function getHistory(string $sessionId): array
    {
        return AIChatHistory::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content', 'created_at'])
            ->toArray();
    }

    /**
     * Xóa lịch sử chat của session
     */
    public function clearHistory(string $sessionId): void
    {
        AIChatHistory::where('session_id', $sessionId)->delete();
    }
}
