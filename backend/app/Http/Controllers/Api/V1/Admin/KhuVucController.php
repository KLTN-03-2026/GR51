<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\KhuVuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KhuVucController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => KhuVuc::orderBy('ten_khu_vuc')->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_khu_vuc' => 'required|string|unique:khu_vucs,ma_khu_vuc',
            'ten_khu_vuc' => 'required|string|max:255',
        ]);

        $khuVuc = KhuVuc::create($request->all());

        return response()->json(['success' => true, 'data' => $khuVuc], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $khuVuc = KhuVuc::find($id);
        if (!$khuVuc) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        $khuVuc->update($request->all());
        return response()->json(['success' => true, 'data' => $khuVuc]);
    }

    public function destroy($id): JsonResponse
    {
        $khuVuc = KhuVuc::find($id);
        if (!$khuVuc) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        if ($khuVuc->bans()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Khu vực đang có bàn, không thể xóa'], 400);
        }

        $khuVuc->delete();
        return response()->json(['success' => true, 'message' => 'Xóa thành công']);
    }
}
