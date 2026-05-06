<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DanhMucController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DanhMuc::orderBy('ten_danh_muc')->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_danh_muc' => 'required|string|unique:danh_mucs,ma_danh_muc',
            'ten_danh_muc' => 'required|string|max:255',
        ]);

        $danhMuc = DanhMuc::create($request->all());

        return response()->json(['success' => true, 'data' => $danhMuc], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $danhMuc = DanhMuc::find($id);
        if (!$danhMuc) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        $danhMuc->update($request->all());
        return response()->json(['success' => true, 'data' => $danhMuc]);
    }

    public function destroy($id): JsonResponse
    {
        $danhMuc = DanhMuc::find($id);
        if (!$danhMuc) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        if ($danhMuc->mons()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Danh mục đang có món ăn, không thể xóa'], 400);
        }

        $danhMuc->delete();
        return response()->json(['success' => true, 'message' => 'Xóa thành công']);
    }
}
