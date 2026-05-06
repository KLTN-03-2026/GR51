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
            'data' => Topping::orderBy('ten_topping')->get()
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
}
