<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\KichCo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KichCoController extends Controller
{
    /**
     * Danh sách kích cỡ
     */
    public function index(): JsonResponse
    {
        $kichCos = KichCo::orderBy('ten_kich_co')->get()->map(function ($kc) {
            return [
                'ma_kich_co' => $kc->ma_kich_co,
                'ten_kich_co' => $kc->ten_kich_co,
                'gia_cong_them' => (float) $kc->gia_cong_them,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $kichCos
        ]);
    }

    /**
     * Thêm kích cỡ
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_kich_co' => 'required|string|unique:kich_cos,ma_kich_co',
            'ten_kich_co' => 'required|string|max:255',
            'gia_cong_them' => 'required|numeric|min:0',
        ]);

        $kichCo = KichCo::create($request->only(['ma_kich_co', 'ten_kich_co', 'gia_cong_them']));

        return response()->json([
            'success' => true,
            'message' => 'Thêm kích cỡ thành công',
            'data' => $kichCo
        ], 201);
    }

    /**
     * Cập nhật kích cỡ
     */
    public function update(Request $request, $maKichCo): JsonResponse
    {
        $kichCo = KichCo::find($maKichCo);
        if (!$kichCo) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy kích cỡ'], 404);
        }

        $request->validate([
            'ten_kich_co' => 'sometimes|required|string|max:255',
            'gia_cong_them' => 'sometimes|required|numeric|min:0',
        ]);

        $kichCo->update($request->only(['ten_kich_co', 'gia_cong_them']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật kích cỡ thành công',
            'data' => $kichCo
        ]);
    }

    /**
     * Xóa kích cỡ
     */
    public function destroy($maKichCo): JsonResponse
    {
        $kichCo = KichCo::find($maKichCo);
        if (!$kichCo) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy kích cỡ'], 404);
        }

        if ($kichCo->chiTietDonHangs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa kích cỡ đang được sử dụng trong đơn hàng.'
            ], 400);
        }

        $kichCo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa kích cỡ thành công'
        ]);
    }
}
