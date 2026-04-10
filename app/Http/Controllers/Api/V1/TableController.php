<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KhuVuc;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index()
    {
        $khuVucs = KhuVuc::with(['bans' => function($q) {
            // Optional: Order tables if needed
            $q->orderBy('ma_ban');
        }])->get();

        $data = $khuVucs->map(function ($kv) {
            return [
                'ma_khu_vuc' => $kv->ma_khu_vuc,
                'ten_khu_vuc' => $kv->ten_khu_vuc,
                'bans' => $kv->bans->map(function ($ban) {
                    return [
                        'ma_ban' => $ban->ma_ban,
                        'ten_ban' => $ban->ten_ban,
                        'trang_thai' => $ban->trang_thai,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'message' => 'Thành công',
            'data' => $data,
        ]);
    }
}
