<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('bans')->insert([
            [
                'ma_ban' => 'BAN01',
                'ten_ban' => 'Bàn 01',
                'ma_khu_vuc' => 'KV01',
                'ma_qr' => 'QR_BAN01',
                'trang_thai' => 'trong',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_ban' => 'BAN02',
                'ten_ban' => 'Bàn 02',
                'ma_khu_vuc' => 'KV01',
                'ma_qr' => 'QR_BAN02',
                'trang_thai' => 'trong',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_ban' => 'BAN03',
                'ten_ban' => 'Bàn 03',
                'ma_khu_vuc' => 'KV02',
                'ma_qr' => 'QR_BAN03',
                'trang_thai' => 'trong',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_ban' => 'BAN04',
                'ten_ban' => 'Bàn 04',
                'ma_khu_vuc' => 'KV02',
                'ma_qr' => 'QR_BAN04',
                'trang_thai' => 'trong',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
