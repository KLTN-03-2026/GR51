<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class CaLam extends Model {
    protected $table = 'ca_lams';
    protected $primaryKey = 'ma_ca_lam';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_ca_lam', 'ma_nhan_su', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'tien_mat_dau_ca', 'tien_mat_he_thong', 'tien_mat_thuc_te', 'tong_doanh_thu', 'ghi_chu', 'trang_thai'];
    
    public function nhanSu() { return $this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su'); }
}
