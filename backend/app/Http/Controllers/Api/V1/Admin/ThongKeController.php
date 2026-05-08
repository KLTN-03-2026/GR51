<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\ChiTietDonHang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ThongKeController extends Controller
{
    private function getDateRange(Request $request)
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay() 
            : Carbon::now()->subDays(30)->startOfDay();
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->endOfDay() 
            : Carbon::now()->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * Báo cáo Doanh thu & Tổng quan
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            [$startDate, $endDate] = $this->getDateRange($request);

            $donHangs = DonHang::whereBetween('created_at', [$startDate, $endDate])->get();

            $tongDoanhThu = $donHangs->where('trang_thai_thanh_toan', 1)->sum('tong_tien');
            $tongDon = $donHangs->count();
            $donHuy = $donHangs->where('trang_thai_don', 3)->count();
            
            $trungBinhDon = $tongDon > 0 ? round($tongDoanhThu / $tongDon, 0) : 0;
            $tyLeHuy = $tongDon > 0 ? round(($donHuy / $tongDon) * 100, 1) : 0;

            // Doanh thu theo ngày (để vẽ biểu đồ)
            $revenueByDate = DonHang::whereBetween('created_at', [$startDate, $endDate])
                ->where('trang_thai_thanh_toan', 1)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(tong_tien) as revenue'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'tong_doanh_thu' => (float)$tongDoanhThu,
                        'tong_don' => $tongDon,
                        'trung_binh_don' => (float)$trungBinhDon,
                        'ty_le_huy' => (float)$tyLeHuy,
                    ],
                    'chart_data' => $revenueByDate
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy báo cáo doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Báo cáo Top Món Bán Chạy & Tỷ trọng Danh mục
     */
    public function bestSellers(Request $request): JsonResponse
    {
        try {
            [$startDate, $endDate] = $this->getDateRange($request);

            // Top 10 món bán chạy nhất
            $topMons = ChiTietDonHang::whereHas('donHang', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate])
                      ->where('trang_thai_don', '!=', 3); // Bỏ qua đơn huỷ
                })
                ->select('mon_id', DB::raw('SUM(so_luong) as tong_ban'), DB::raw('SUM(so_luong * don_gia) as tong_doanh_thu_mon'))
                ->groupBy('mon_id')
                ->orderByDesc('tong_ban')
                ->limit(10)
                ->with(['mon.danhMuc'])
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->mon_id,
                        'ten_mon' => $item->mon ? $item->mon->ten_mon : 'N/A',
                        'danh_muc' => $item->mon && $item->mon->danhMuc ? $item->mon->danhMuc->ten_danh_muc : 'Khác',
                        'tong_ban' => (int)$item->tong_ban,
                        'tong_doanh_thu_mon' => (float)$item->tong_doanh_thu_mon,
                    ];
                });

            // Tỷ trọng doanh thu theo Danh Mục
            $danhMucData = [];
            foreach ($topMons as $mon) {
                $dm = $mon['danh_muc'];
                if (!isset($danhMucData[$dm])) {
                    $danhMucData[$dm] = 0;
                }
                $danhMucData[$dm] += $mon['tong_doanh_thu_mon'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'top_mons' => $topMons,
                    'danh_muc_chart' => $danhMucData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy top món: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tổng quan Đơn hàng (Trạng thái, Phương thức thanh toán)
     */
    public function ordersOverview(Request $request): JsonResponse
    {
        try {
            [$startDate, $endDate] = $this->getDateRange($request);

            $donHangs = DonHang::whereBetween('created_at', [$startDate, $endDate])->get();

            $byStatus = [
                'cho_xu_ly' => $donHangs->where('trang_thai_don', 0)->count(),
                'dang_pha' => $donHangs->where('trang_thai_don', 1)->count(),
                'hoan_thanh' => $donHangs->where('trang_thai_don', 2)->count(),
                'da_huy' => $donHangs->where('trang_thai_don', 3)->count(),
            ];

            $byPayment = [
                'tien_mat' => $donHangs->where('phuong_thuc_thanh_toan', 'tien_mat')->count(),
                'chuyen_khoan' => $donHangs->where('phuong_thuc_thanh_toan', 'chuyen_khoan')->count(),
            ];

            $cancelReasons = $donHangs->where('trang_thai_don', 3)
                ->whereNotNull('ly_do_huy')
                ->groupBy('ly_do_huy')
                ->map(function ($group, $reason) {
                    return [
                        'ly_do' => $reason,
                        'so_luong' => $group->count()
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'by_status' => $byStatus,
                    'by_payment' => $byPayment,
                    'cancel_reasons' => $cancelReasons
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lấy tổng quan đơn: ' . $e->getMessage()
            ], 500);
        }
    }
}
