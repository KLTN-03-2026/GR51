<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CongThuc extends Model
{
    protected $table = 'cong_thucs';
    protected $fillable = ['mon_id', 'nguyen_lieu_id', 'so_luong_can'];

    public function mon()
    {
        return $this->belongsTo(Mon::class, 'mon_id');
    }

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }
}
