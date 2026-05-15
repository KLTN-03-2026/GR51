<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToppingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Topping::with('congThucs.nguyenLieu')->orderBy('ten_topping')->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_topping' => 'required|string|unique:toppings,ma_topping',
            'ten_topping' => 'required|string|max:255',
            'gia_tien' => 'required|numeric|min:0',
            'trang_thai' => 'required|integer',
        ]);

        $topping = Topping::create($request->all());

        return response()->json(['success' => true, 'data' => $topping], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $topping = Topping::find($id);
        if (!$topping) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        $request->validate([
            'ten_topping' => 'sometimes|required|string|max:255',
            'gia_tien' => 'sometimes|required|numeric|min:0',
            'trang_thai' => 'sometimes|required|integer',
        ]);

        $topping->update($request->only(['ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai']));
        return response()->json(['success' => true, 'data' => $topping]);
    }

    public function destroy($id): JsonResponse
    {
        $topping = Topping::find($id);
        if (!$topping) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        // Kiểm tra xem topping có đang nằm trong đơn hàng đang xử lý không
        $isUsed = $topping->chiTietToppings()->whereHas('chiTietDonHang.donHang', function($q) {
            $q->whereIn('trang_thai_don', [0, 1]); // 0: Chờ xác nhận, 1: Đang pha
        })->exists();

        if ($isUsed) {
            return response()->json([
                'success' => false, 
                'message' => 'Không thể xóa Topping đang có trong đơn hàng chờ pha chế.'
            ], 400);
        }

        $topping->delete();
        return response()->json(['success' => true, 'message' => 'Xóa thành công']);
    }

    /**
     * Cập nhật công thức cho topping
     */
    public function saveCongThuc(Request $request, $id): JsonResponse
    {
        $topping = Topping::find($id);
        if (!$topping) return response()->json(['success' => false, 'message' => 'Không tìm thấy topping'], 404);

        $request->validate([
            'cong_thuc' => 'required|array',
            'cong_thuc.*.nguyen_lieu_id' => 'required|exists:nguyen_lieus,id',
            'cong_thuc.*.so_luong_can' => 'required|numeric|min:0',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            
            // Xóa công thức cũ
            $topping->congThucs()->delete();

            // Thêm công thức mới
            foreach ($request->input('cong_thuc') as $item) {
                if ($item['so_luong_can'] > 0) {
                    $topping->congThucs()->create([
                        'nguyen_lieu_id' => $item['nguyen_lieu_id'],
                        'so_luong_can' => $item['so_luong_can'],
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật công thức thành công',
                'data' => $topping->load('congThucs.nguyenLieu')
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
