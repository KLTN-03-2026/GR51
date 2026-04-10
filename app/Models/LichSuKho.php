<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class LichSuKho extends Model {
    protected $table = 'lich_su_khos';
    protected $primaryKey = 'ma_ls_kho';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_ls_kho', 'ma_nguyen_lieu', 'ma_nhan_su', 'loai_giao_dich', 'so_luong_thay_doi'];
    
    public function nguyenLieu() { return $this->belongsTo(NguyenLieu::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
    public function nhanSu() { return $this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su'); }
}
