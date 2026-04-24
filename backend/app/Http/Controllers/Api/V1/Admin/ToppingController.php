<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToppingController extends Controller
{
    /**
     * Danh sách topping
     */
    public function index(): JsonResponse
    {
        $toppings = Topping::orderBy('ten_topping')->get()->map(function ($tp) {
            return [
                'ma_topping' => $tp->ma_topping,
                'ten_topping' => $tp->ten_topping,
                'hinh_anh' => $tp->hinh_anh,
                'gia_tien' => (float) $tp->gia_tien,
                'trang_thai' => $tp->trang_thai,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $toppings
        ]);
    }

    /**
     * Thêm topping
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_topping' => 'required|string|unique:toppings,ma_topping',
            'ten_topping' => 'required|string|max:255',
            'gia_tien' => 'required|numeric|min:0',
            'trang_thai' => 'required|string',
            'hinh_anh' => 'nullable|string',
        ]);

        $topping = Topping::create($request->only([
            'ma_topping', 'ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Thêm topping thành công',
            'data' => $topping
        ], 201);
    }

    /**
     * Cập nhật topping
     */
    public function update(Request $request, $maTopping): JsonResponse
    {
        $topping = Topping::find($maTopping);
        if (!$topping) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy topping'], 404);
        }

        $request->validate([
            'ten_topping' => 'sometimes|required|string|max:255',
            'gia_tien' => 'sometimes|required|numeric|min:0',
            'trang_thai' => 'sometimes|required|string',
            'hinh_anh' => 'nullable|string',
        ]);

        $topping->update($request->only(['ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật topping thành công',
            'data' => $topping
        ]);
    }

    /**
     * Xóa topping
     */
    public function destroy($maTopping): JsonResponse
    {
        $topping = Topping::find($maTopping);
        if (!$topping) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy topping'], 404);
        }

        if ($topping->chiTietToppings()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa topping đang được sử dụng trong đơn hàng.'
            ], 400);
        }

        $topping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa topping thành công'
        ]);
    }
}
