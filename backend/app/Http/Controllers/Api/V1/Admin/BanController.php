<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ban;
use App\Models\KhuVuc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BanController extends Controller
{
    /**
     * Danh sách bàn
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ban::with('khuVuc');

        if ($request->has('khu_vuc_id') && $request->khu_vuc_id) {
            $query->where('khu_vuc_id', $request->khu_vuc_id);
        }

        if ($request->has('trang_thai') && $request->trang_thai !== null) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $bans = $query->orderBy('ma_ban')->get()->map(function ($ban) {
            return [
                'id' => $ban->id,
                'ma_ban' => $ban->ma_ban,
                'ten_ban' => $ban->ten_ban,
                'khu_vuc_id' => $ban->khu_vuc_id,
                'ten_khu_vuc' => $ban->khuVuc ? $ban->khuVuc->ten_khu_vuc : null,
                'ma_qr' => $ban->ma_qr,
                'trang_thai' => (int)$ban->trang_thai,
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
            'khu_vuc_id' => 'required|integer|exists:khu_vucs,id',
            'ma_qr' => 'required|string',
            'trang_thai' => 'required|integer',
        ]);

        $ban = Ban::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Thêm bàn thành công',
            'data' => $ban
        ], 201);
    }

    /**
     * Cập nhật bàn
     * @param string|int $id
     */
    public function update(Request $request, $id): JsonResponse
    {
        $ban = Ban::find($id);
        if (!$ban) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bàn'], 404);
        }

        $request->validate([
            'ten_ban' => 'sometimes|required|string|max:255',
            'khu_vuc_id' => 'sometimes|required|integer|exists:khu_vucs,id',
            'ma_qr' => 'sometimes|required|string',
            'trang_thai' => 'sometimes|required|integer',
        ]);

        $ban->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật bàn thành công',
            'data' => $ban
        ]);
    }

    /**
     * Xóa bàn
     * @param string|int $id
     */
    public function destroy($id): JsonResponse
    {
        $ban = Ban::find($id);
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
