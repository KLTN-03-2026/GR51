<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mon extends Model
{
    protected $table = 'mons';
    protected $fillable = ['ma_mon', 'danh_muc_id', 'ten_mon', 'hinh_anh', 'gia_ban', 'cong_thuc', 'trang_thai'];

    public function danhMuc()
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function chiTietDonHangs()
    {
        return $this->hasMany(ChiTietDonHang::class, 'mon_id');
    }

    public function congThucs()
    {
        return $this->hasMany(CongThuc::class, 'mon_id');
    }

    public function toppings()
    {
        return $this->belongsToMany(Topping::class, 'mon_topping', 'mon_id', 'topping_id');
    }

    public function sizes()
    {
        return $this->belongsToMany(KichCo::class, 'mon_kich_co', 'mon_id', 'kich_co_id');
    }
}
