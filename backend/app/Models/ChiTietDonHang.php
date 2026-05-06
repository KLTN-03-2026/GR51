<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietDonHang extends Model
{
    protected $table = 'chi_tiet_don_hangs';
    protected $fillable = [
        'ma_chi_tiet',
        'don_hang_id',
        'mon_id',
        'kich_co_id',
        'so_luong',
        'ghi_chu',
        'don_gia'
    ];

    public function donHang()
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function mon()
    {
        return $this->belongsTo(Mon::class, 'mon_id');
    }

    public function kichCo()
    {
        return $this->belongsTo(KichCo::class, 'kich_co_id');
    }

    public function chiTietToppings()
    {
        return $this->hasMany(ChiTietTopping::class, 'chi_tiet_don_hang_id');
    }
}
