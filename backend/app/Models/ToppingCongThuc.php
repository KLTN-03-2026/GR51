<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToppingCongThuc extends Model
{
    protected $table = 'topping_cong_thucs';
    protected $fillable = ['topping_id', 'nguyen_lieu_id', 'so_luong_can'];

    public function topping()
    {
        return $this->belongsTo(Topping::class, 'topping_id');
    }

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }
}
