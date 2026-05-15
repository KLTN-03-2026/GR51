<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topping extends Model
{
    use SoftDeletes;

    protected $table = 'toppings';
    protected $fillable = ['ma_topping', 'ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai'];

    public function chiTietToppings()
    {
        return $this->hasMany(ChiTietTopping::class, 'topping_id');
    }

    public function mons()
    {
        return $this->belongsToMany(Mon::class, 'mon_topping', 'topping_id', 'mon_id');
    }

    public function congThucs()
    {
        return $this->hasMany(ToppingCongThuc::class, 'topping_id');
    }
}
