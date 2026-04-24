<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DanhMucController extends Controller
{
    /**
     * Danh sách tất cả danh mục
     */
    public function index(): JsonResponse
    {
        $danhMucs = DanhMuc::withCount('mons')->orderBy('ten_danh_muc')->get();

        return response()->json([
            'success' => true,
            'data' => $danhMucs
        ]);
    }

    /**
     * Thêm danh mục mới
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_danh_muc' => 'required|string|unique:danh_mucs,ma_danh_muc',
            'ten_danh_muc' => 'required|string|max:255',
        ]);

        $danhMuc = DanhMuc::create([
            'ma_danh_muc' => $request->input('ma_danh_muc'),
            'ten_danh_muc' => $request->input('ten_danh_muc'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thêm danh mục thành công',
            'data' => $danhMuc
        ], 201);
    }

    /**
     * Cập nhật danh mục
     */
    public function update(Request $request, $maDanhMuc): JsonResponse
    {
        $danhMuc = DanhMuc::find($maDanhMuc);
        if (!$danhMuc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy danh mục'], 404);
        }

        $request->validate([
            'ten_danh_muc' => 'required|string|max:255',
        ]);

        $danhMuc->update(['ten_danh_muc' => $request->input('ten_danh_muc')]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh mục thành công',
            'data' => $danhMuc
        ]);
    }

    /**
     * Xóa danh mục
     */
    public function destroy($maDanhMuc): JsonResponse
    {
        $danhMuc = DanhMuc::find($maDanhMuc);
        if (!$danhMuc) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy danh mục'], 404);
        }

        // Kiểm tra có món nào thuộc danh mục không
        if ($danhMuc->mons()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa danh mục đang có món ăn. Hãy xóa hoặc chuyển các món trước.'
            ], 400);
        }

        $danhMuc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa danh mục thành công'
        ]);
    }
}
