<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KhuVuc;
use App\Models\Ban;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(): JsonResponse
    {
        $khuVucs = KhuVuc::with('bans')->get();

        $data = $khuVucs->map(function ($kv) {
            return [
                'id' => $kv->id,
                'ma_khu_vuc' => $kv->ma_khu_vuc,
                'ten_khu_vuc' => $kv->ten_khu_vuc,
                'bans' => $kv->bans->map(function ($ban) {
                    return [
                        'id' => $ban->id,
                        'ma_ban' => $ban->ma_ban,
                        'ten_ban' => $ban->ten_ban,
                        'trang_thai' => (int)$ban->trang_thai,
                    ];
                }),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id): JsonResponse
    {
        // Hỗ trợ tìm theo cả ID (số) hoặc mã bàn (chuỗi)
        $ban = is_numeric($id) 
            ? Ban::find($id) 
            : Ban::where('ma_ban', $id)->first();
        
        if (!$ban) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy bàn'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ban->id,
                'ma_ban' => $ban->ma_ban,
                'ten_ban' => $ban->ten_ban,
                'trang_thai' => (int)$ban->trang_thai,
            ]
        ]);
    }
}
