<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KichCoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('kich_cos')->insert([
            [
                'ma_kich_co' => 'SIZE_M',
                'ten_kich_co' => 'Size M',
                'gia_cong_them' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_kich_co' => 'SIZE_L',
                'ten_kich_co' => 'Size L',
                'gia_cong_them' => 10000,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
