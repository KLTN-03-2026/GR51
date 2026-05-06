<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\KichCo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KichCoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => KichCo::orderBy('ten_kich_co')->get()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ma_kich_co' => 'required|string|unique:kich_cos,ma_kich_co',
            'ten_kich_co' => 'required|string|max:255',
            'gia_cong_them' => 'required|numeric|min:0',
        ]);

        $kichCo = KichCo::create($request->all());

        return response()->json(['success' => true, 'data' => $kichCo], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $kichCo = KichCo::find($id);
        if (!$kichCo) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        $kichCo->update($request->all());
        return response()->json(['success' => true, 'data' => $kichCo]);
    }

    public function destroy($id): JsonResponse
    {
        $kichCo = KichCo::find($id);
        if (!$kichCo) return response()->json(['success' => false, 'message' => 'Không tìm thấy'], 404);

        $kichCo->delete();
        return response()->json(['success' => true, 'message' => 'Xóa thành công']);
    }
}
