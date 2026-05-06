<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuKho extends Model
{
    protected $table = 'lich_su_khos';
    protected $fillable = ['ma_ls_kho', 'nguyen_lieu_id', 'nhan_su_id', 'loai_giao_dich', 'so_luong_thay_doi'];

    public function nguyenLieu()
    {
        return $this->belongsTo(NguyenLieu::class, 'nguyen_lieu_id');
    }

    public function nhanSu()
    {
        return $this->belongsTo(NhanSu::class, 'nhan_su_id');
    }
}
