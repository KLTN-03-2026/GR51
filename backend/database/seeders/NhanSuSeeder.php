<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class NhanSuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        DB::table('nhan_sus')->insert([
            [
                'ma_nhan_su' => 'NV01',
                'ten_dang_nhap' => 'admin',
                'mat_khau' => Hash::make('123456'),
                'ma_pin' => Hash::make('1111'),
                'ho_ten' => 'Quản Lý',
                'so_dien_thoai' => '0901234567',
                'vai_tro' => 'quan_ly',
                'trang_thai' => 'hoat_dong',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'ma_nhan_su' => 'NV02',
                'ten_dang_nhap' => 'nhanvien',
                'mat_khau' => null,
                'ma_pin' => Hash::make('1234'),
                'ho_ten' => 'Nhân Viên Thu Ngân',
                'so_dien_thoai' => '0901234568',
                'vai_tro' => 'nhan_vien',
                'trang_thai' => 'hoat_dong',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}
