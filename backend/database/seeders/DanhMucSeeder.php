<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DanhMucSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('danh_mucs')->insert([
            [
                'ma_danh_muc' => 'DM01',
                'ten_danh_muc' => 'Cà phê',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_danh_muc' => 'DM02',
                'ten_danh_muc' => 'Trà Sữa',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
