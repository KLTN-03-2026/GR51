<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NhanSu;
use Illuminate\Support\Facades\Hash;

class NhanSuSeeder extends Seeder
{
    public function run(): void
    {
        NhanSu::updateOrCreate(
            ['ten_dang_nhap' => 'admin'], // Điều kiện tìm kiếm
            [
                'ma_nhan_su' => 'ADMIN01',
                'mat_khau' => Hash::make('admin123'),
                'ma_pin' => '123456',
                'ho_ten' => 'Hệ thống Quản trị',
                'so_dien_thoai' => '0900000000',
                'vai_tro' => 'admin',
                'trang_thai' => 1,
            ]
        );
    }
}
