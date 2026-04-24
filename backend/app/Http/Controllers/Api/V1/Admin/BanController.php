<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ban;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BanController extends Controller
{
    /**
     * Danh sách bàn (kèm khu vực)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ban::with('khuVuc:ma_khu_vuc,ten_khu_vuc');

        if ($request->has('ma_khu_vuc') && $request->ma_khu_vuc) {
            $query->where('ma_khu_vuc', $request->ma_khu_vuc);
        }

        if ($request->has('trang_thai') && $request->trang_thai) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $bans = $query->orderBy('ma_ban')->get()->map(function ($ban) {
            return [
                'ma_ban' => $ban->ma_ban,
                'ten_ban' => $ban->ten_ban,
                'ma_khu_vuc' => $ban->ma_khu_vuc,
                'ten_khu_vuc' => $ban->khuVuc ? $ban->khuVuc->ten_khu_vuc : null,
                'ma_qr' => $ban->ma_qr,
                'trang_thai' => $ban->trang_thai,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $bans
        ]);
    }

    /**
     * Thêm bàn
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_ban' => 'required|string|unique:bans,ma_ban',
            'ten_ban' => 'required|string|max:255',
            'ma_khu_vuc' => 'required|string|exists:khu_vucs,ma_khu_vuc',
            'ma_qr' => 'required|string',
            'trang_thai' => 'required|string',
        ]);

        $ban = Ban::create($request->only(['ma_ban', 'ten_ban', 'ma_khu_vuc', 'ma_qr', 'trang_thai']));

        return response()->json([
            'success' => true,
            'message' => 'Thêm bàn thành công',
            'data' => $ban
        ], 201);
    }

    /**
     * Cập nhật bàn
     */
    public function update(Request $request, $maBan): JsonResponse
    {
        $ban = Ban::find($maBan);
        if (!$ban) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bàn'], 404);
        }

        $request->validate([
            'ten_ban' => 'sometimes|required|string|max:255',
            'ma_khu_vuc' => 'sometimes|required|string|exists:khu_vucs,ma_khu_vuc',
            'ma_qr' => 'sometimes|required|string',
            'trang_thai' => 'sometimes|required|string',
        ]);

        $ban->update($request->only(['ten_ban', 'ma_khu_vuc', 'ma_qr', 'trang_thai']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bàn thành công',
            'data' => $ban
        ]);
    }

    /**
     * Xóa bàn
     */
    public function destroy($maBan): JsonResponse
    {
        $ban = Ban::find($maBan);
        if (!$ban) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bàn'], 404);
        }

        if ($ban->donHangs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa bàn đã có đơn hàng. Hãy chuyển trạng thái thay vì xóa.'
            ], 400);
        }

        $ban->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa bàn thành công'
        ]);
    }
}
