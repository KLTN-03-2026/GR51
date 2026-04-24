<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhGia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DanhGiaController extends Controller
{
    /**
     * Danh sách đánh giá (filter + thống kê)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DanhGia::with('donHang:ma_don_hang,ma_ban,tong_tien,created_at');

            // Filter theo số sao
            if ($request->has('so_sao') && $request->so_sao) {
                $query->where('so_sao', $request->so_sao);
            }

            // Filter theo ngày
            if ($request->has('tu_ngay') && $request->tu_ngay) {
                $query->whereDate('created_at', '>=', $request->tu_ngay);
            }
            if ($request->has('den_ngay') && $request->den_ngay) {
                $query->whereDate('created_at', '<=', $request->den_ngay);
            }

            $danhGias = $query->orderByDesc('created_at')
                ->limit(200)
                ->get()
                ->map(function ($dg) {
                    return [
                        'ma_danh_gia' => $dg->ma_danh_gia,
                        'ma_don_hang' => $dg->ma_don_hang,
                        'so_sao' => $dg->so_sao,
                        'binh_luan' => $dg->binh_luan,
                        'tong_tien_don' => $dg->donHang ? (float) $dg->donHang->tong_tien : null,
                        'thoi_gian' => $dg->created_at ? $dg->created_at->format('H:i d/m/Y') : null,
                    ];
                });

            // Thống kê tổng quan
            $thongKe = [
                'trung_binh' => round((float) DanhGia::avg('so_sao'), 1),
                'tong_danh_gia' => DanhGia::count(),
                'phan_bo' => [
                    '5_sao' => DanhGia::where('so_sao', 5)->count(),
                    '4_sao' => DanhGia::where('so_sao', 4)->count(),
                    '3_sao' => DanhGia::where('so_sao', 3)->count(),
                    '2_sao' => DanhGia::where('so_sao', 2)->count(),
                    '1_sao' => DanhGia::where('so_sao', 1)->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'danh_gias' => $danhGias,
                    'thong_ke' => $thongKe,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách đánh giá: ' . $e->getMessage()
            ], 500);
        }
    }
}
