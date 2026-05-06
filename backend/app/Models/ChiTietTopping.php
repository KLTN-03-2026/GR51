<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChiTietTopping extends Model
{
    protected $table = 'chi_tiet_toppings';
    protected $fillable = [
        'ma_chi_tiet_topping',
        'chi_tiet_don_hang_id',
        'topping_id',
        'so_luong',
        'gia_tien'
    ];

    public function chiTietDonHang()
    {
        return $this->belongsTo(ChiTietDonHang::class, 'chi_tiet_don_hang_id');
    }

    public function topping()
    {
        return $this->belongsTo(Topping::class, 'topping_id');
    }
}
