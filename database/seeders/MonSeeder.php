<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('mons')->insert([
            [
                'ma_mon' => 'MON01',
                'ma_danh_muc' => 'DM01',
                'ten_mon' => 'Cà phê đen đá',
                'gia_ban' => 25000,
                'trang_thai' => 'hoat_dong',
                'hinh_anh' => null,
                'cong_thuc' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_mon' => 'MON02',
                'ma_danh_muc' => 'DM01',
                'ten_mon' => 'Bạc xỉu',
                'gia_ban' => 30000,
                'trang_thai' => 'hoat_dong',
                'hinh_anh' => null,
                'cong_thuc' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_mon' => 'MON03',
                'ma_danh_muc' => 'DM02',
                'ten_mon' => 'Trà sữa trân châu',
                'gia_ban' => 40000,
                'trang_thai' => 'hoat_dong',
                'hinh_anh' => null,
                'cong_thuc' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_mon' => 'MON04',
                'ma_danh_muc' => 'DM02',
                'ten_mon' => 'Trà đào cam sả',
                'gia_ban' => 35000,
                'trang_thai' => 'hoat_dong',
                'hinh_anh' => null,
                'cong_thuc' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
