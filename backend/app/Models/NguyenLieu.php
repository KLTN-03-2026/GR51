<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NguyenLieu extends Model
{
    protected $table = 'nguyen_lieus';
    protected $fillable = ['ma_nguyen_lieu', 'ten_nguyen_lieu', 'hinh_anh', 'don_vi_tinh', 'ton_kho', 'muc_canh_bao', 'trang_thai'];

    public function congThucs()
    {
        return $this->hasMany(CongThuc::class, 'nguyen_lieu_id');
    }

    public function lichSuKhos()
    {
        return $this->hasMany(LichSuKho::class, 'nguyen_lieu_id');
    }
}
