<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topping extends Model
{
    protected $table = 'toppings';
    protected $fillable = ['ma_topping', 'ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai'];

    public function chiTietToppings()
    {
        return $this->hasMany(ChiTietTopping::class, 'topping_id');
    }
}
