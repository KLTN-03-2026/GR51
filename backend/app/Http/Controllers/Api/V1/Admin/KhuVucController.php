<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\KhuVuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KhuVucController extends Controller
{
    /**
     * Danh sách khu vực (kèm số lượng bàn)
     */
    public function index(): JsonResponse
    {
        $khuVucs = KhuVuc::withCount('bans')->orderBy('ten_khu_vuc')->get();

        return response()->json([
            'success' => true,
            'data' => $khuVucs
        ]);
    }

    /**
     * Thêm khu vực
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_khu_vuc' => 'required|string|unique:khu_vucs,ma_khu_vuc',
            'ten_khu_vuc' => 'required|string|max:255',
        ]);

        $khuVuc = KhuVuc::create($request->only(['ma_khu_vuc', 'ten_khu_vuc']));

        return response()->json([
            'success' => true,
            'message' => 'Thêm khu vực thành công',
            'data' => $khuVuc
        ], 201);
    }

    /**
     * Cập nhật khu vực
     */
    public function update(Request $request, $maKhuVuc): JsonResponse
    {
        $khuVuc = KhuVuc::find($maKhuVuc);
        if (!$khuVuc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy khu vực'], 404);
        }

        $request->validate([
            'ten_khu_vuc' => 'required|string|max:255',
        ]);

        $khuVuc->update(['ten_khu_vuc' => $request->input('ten_khu_vuc')]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật khu vực thành công',
            'data' => $khuVuc
        ]);
    }

    /**
     * Xóa khu vực
     */
    public function destroy($maKhuVuc): JsonResponse
    {
        $khuVuc = KhuVuc::find($maKhuVuc);
        if (!$khuVuc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy khu vực'], 404);
        }

        if ($khuVuc->bans()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa khu vực đang có bàn. Hãy xóa các bàn trước.'
            ], 400);
        }

        $khuVuc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa khu vực thành công'
        ]);
    }
}
