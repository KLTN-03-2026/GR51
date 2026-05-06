<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonHang extends Model
{
    protected $table = 'don_hangs';
    protected $fillable = [
        'ma_don_hang',
        'ban_id',
        'nhan_su_id',
        'loai_don',
        'tong_tien',
        'phuong_thuc_thanh_toan',
        'trang_thai_thanh_toan',
        'trang_thai_don',
        'ly_do_huy'
    ];

    protected $appends = ['da_danh_gia'];

    public function getDaDanhGiaAttribute()
    {
        return $this->danhGia()->exists();
    }

    public function ban()
    {
        return $this->belongsTo(Ban::class, 'ban_id');
    }

    public function nhanSu()
    {
        return $this->belongsTo(NhanSu::class, 'nhan_su_id');
    }

    public function chiTietDonHangs()
    {
        return $this->hasMany(ChiTietDonHang::class, 'don_hang_id');
    }

    public function danhGia()
    {
        return $this->hasOne(DanhGia::class, 'don_hang_id');
    }
}
