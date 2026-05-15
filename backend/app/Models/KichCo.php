<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KichCo extends Model
{
    use SoftDeletes;

    protected $table = 'kich_cos';
    protected $fillable = ['ma_kich_co', 'ten_kich_co', 'gia_cong_them'];

    public function chiTietDonHangs()
    {
        return $this->hasMany(ChiTietDonHang::class, 'kich_co_id');
    }

    public function mons()
    {
        return $this->belongsToMany(Mon::class, 'mon_kich_co', 'kich_co_id', 'mon_id');
    }
}
