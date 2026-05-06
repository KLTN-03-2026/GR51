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
    public function chat(string $message, string $sessionId, int $userId): string
    {
        try {
            $history = AIChatHistory::where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->values();

            $context = $this->buildContext();
            $systemPrompt = $this->buildSystemPrompt($context);

            $contents = [];
            foreach ($history as $msg) {
                $contents[] = [
                    'role' => $msg->role === 'user' ? 'user' : 'model',
                    'parts' => [['text' => $msg->content]]
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]]
            ];

            $models = [
                ['name' => 'gemini-1.5-flash-latest', 'ver' => 'v1beta'],
                ['name' => 'gemini-1.5-pro-latest', 'ver' => 'v1beta'],
                ['name' => 'gemini-1.5-flash', 'ver' => 'v1beta'],
                ['name' => 'gemini-1.0-pro', 'ver' => 'v1beta'],
                ['name' => 'gemini-2.0-flash', 'ver' => 'v1beta'],
            ];
            $reply = null;

            foreach ($models as $m) {
                $modelName = $m['name'];
                $apiVersion = $m['ver'];

                $payload = [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topP' => 0.9,
                        'maxOutputTokens' => 2048,
                    ]
                ];

                // All models here use v1beta, but let's be robust
                if ($apiVersion === 'v1beta') {
                    $payload['system_instruction'] = [
                        'parts' => [['text' => $systemPrompt]]
                    ];
                } else {
                    // Fallback for v1 (not expected here but for safety)
                    array_unshift($payload['contents'], [
                        'role' => 'user',
                        'parts' => [['text' => "SYSTEM INSTRUCTION: " . $systemPrompt]]
                    ]);
                }
                
                $response = Http::timeout(30)->post(
                    "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$modelName}:generateContent?key={$this->apiKey}",
                    $payload
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    break;
                }

                Log::warning("AIService model {$modelName} ({$apiVersion}) failed. Status: " . $response->status() . " Body: " . $response->body());

                if ($response->status() === 429) {
                    sleep(1);
                    continue;
                }
            }

            if (!$reply) {
                return 'Xin lỗi, tôi đang gặp sự cố kết nối. Vui lòng thử lại sau ít phút. ⏳';
            }

            AIChatHistory::create([
                'nhan_su_id' => $userId,
                'session_id' => $sessionId,
                'role' => 'user',
                'content' => $message,
            ]);

            AIChatHistory::create([
                'nhan_su_id' => $userId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return $reply;
        } catch (\Exception $e) {
            Log::error('AIService error: ' . $e->getMessage());
            return 'Xin lỗi, đã xảy ra lỗi khi xử lý câu hỏi của bạn.';
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
            ->where('trang_thai_thanh_toan', 1) // 1: Đã thanh toán
            ->sum('tong_tien');

        $tongDonHomNay = DonHang::whereDate('created_at', $today)->count();

        $doanhThuHomQua = DonHang::whereDate('created_at', $yesterday)
            ->where('trang_thai_thanh_toan', 1)
            ->sum('tong_tien');

        // Doanh thu theo từng ngày trong tuần
        $doanhThuTungNgay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $rev = DonHang::whereDate('created_at', $date)
                ->where('trang_thai_thanh_toan', 1)
                ->sum('tong_tien');
            $orders = DonHang::whereDate('created_at', $date)->count();
            $doanhThuTungNgay[] = $date->format('d/m') . ": " . number_format($rev) . "đ ({$orders} đơn)";
        }

        // --- Đơn hàng đang xử lý ---
        $donDangPha = DonHang::where('trang_thai_don', 1) // 1: Đang pha
            ->whereDate('created_at', $today)->count();

        $donChoXuLy = DonHang::where('trang_thai_don', 0) // 0: Chờ xử lý
            ->whereDate('created_at', $today)->count();

        // --- Top món bán chạy ---
        $topMon = ChiTietDonHang::select('mon_id', DB::raw('SUM(so_luong) as tong_ban'), DB::raw('SUM(so_luong * don_gia) as tong_doanh_thu'))
            ->whereHas('donHang', function ($q) {
                $q->where('trang_thai_thanh_toan', 1);
            })
            ->groupBy('mon_id')
            ->orderByDesc('tong_ban')
            ->limit(5)
            ->with('mon')
            ->get()
            ->map(function ($item) {
                $tenMon = $item->mon ? $item->mon->ten_mon : 'N/A';
                return "- {$tenMon}: {$item->tong_ban} ly, doanh thu " . number_format($item->tong_doanh_thu) . "đ";
            });

        // --- Tồn kho ---
        $nguyenLieuCanhBao = NguyenLieu::where('trang_thai', 1)
            ->where(function ($q) {
                $q->where('ton_kho', '<=', 0)
                    ->orWhereColumn('ton_kho', '<=', 'muc_canh_bao');
            })
            ->orderBy('ton_kho')
            ->get()
            ->map(function ($nl) {
                $status = $nl->ton_kho <= 0 ? '🔴 HẾT HÀNG' : '🟡 SẮP HẾT';
                return "- {$nl->ten_nguyen_lieu}: còn {$nl->ton_kho} {$nl->don_vi_tinh} (mức cảnh báo: {$nl->muc_canh_bao}) {$status}";
            });

        // --- Đánh giá ---
        $danhGiaTB = DanhGia::avg('so_sao');
        $tongDanhGia = DanhGia::count();

        $context = "📊 DỮ LIỆU KINH DOANH SMART CAFE (" . now()->format('H:i d/m/Y') . ")\n\n";
        $context .= "═══ DOANH THU ═══\n";
        $context .= "Hôm nay: " . number_format($doanhThuHomNay) . "đ ({$tongDonHomNay} đơn)\n";
        $context .= "Hôm qua: " . number_format($doanhThuHomQua) . "đ\n";
        $context .= "Doanh thu 7 ngày qua:\n" . implode("\n", $doanhThuTungNgay) . "\n";
        $context .= "\n═══ TRẠM PHA CHẾ ═══\n";
        $context .= "Đang pha: {$donDangPha} | Đang chờ: {$donChoXuLy}\n";
        $context .= "\n═══ TOP MÓN BÁN CHẠY ═══\n" . $topMon->implode("\n") . "\n";
        $context .= "\n═══ CẢNH BÁO KHO ═══\n" . ($nguyenLieuCanhBao->isEmpty() ? "✅ Ổn định" : $nguyenLieuCanhBao->implode("\n")) . "\n";
        $context .= "\n═══ KHÁCH HÀNG ═══\nTrung bình: " . ($danhGiaTB ? round($danhGiaTB, 1) . "⭐" : "Chưa có") . " ({$tongDanhGia} lượt)";

        return $context;
    }

    private function buildSystemPrompt(string $context): string
    {
        return "Bạn là Cafe AI - Trợ lý quản lý Smart Cafe. Phân tích dữ liệu thực tế và hỗ trợ chủ quán.
Dữ liệu hiện tại:
{$context}
Quy tắc:
1. Trả lời tiếng Việt, chuyên nghiệp.
2. Dựa trên số liệu thực, không bịa đặt.
3. Gợi ý hành động thực tế (nhập hàng, khuyến mãi).";
    }

    public function getHistory(string $sessionId): array
    {
        return AIChatHistory::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content', 'created_at'])
            ->toArray();
    }

    public function clearHistory(string $sessionId): void
    {
        AIChatHistory::where('session_id', $sessionId)->delete();
    }
}
