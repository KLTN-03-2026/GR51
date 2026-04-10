<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KhuVucSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('khu_vucs')->insert([
            [
                'ma_khu_vuc' => 'KV01',
                'ten_khu_vuc' => 'Tầng 1',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_khu_vuc' => 'KV02',
                'ten_khu_vuc' => 'Tầng 2',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
