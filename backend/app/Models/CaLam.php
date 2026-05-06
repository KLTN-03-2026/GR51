<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaLam extends Model
{
    protected $table = 'ca_lams';
    protected $fillable = [
        'ma_ca_lam',
        'nhan_su_id',
        'thoi_gian_bat_dau',
        'thoi_gian_ket_thuc',
        'tien_mat_dau_ca',
        'tien_mat_he_thong',
        'tien_mat_thuc_te',
        'tong_doanh_thu',
        'ghi_chu',
        'trang_thai'
    ];

    public function nhanSu()
    {
        return $this->belongsTo(NhanSu::class, 'nhan_su_id');
    }
}
