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

        $topping->update($request->all());
        return response()->json(['success' => true, 'data' => $topping]);
    }

    public function destroy($id): JsonResponse
    {
        $topping = Topping::find($id);
        if (!$topping) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

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
