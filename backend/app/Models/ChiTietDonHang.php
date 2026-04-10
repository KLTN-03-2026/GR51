<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ChiTietDonHang extends Model {
    protected $table = 'chi_tiet_don_hangs';
    protected $primaryKey = 'ma_chi_tiet';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_chi_tiet', 'ma_don_hang', 'ma_mon', 'ma_kich_co', 'so_luong', 'ghi_chu', 'don_gia'];
    
    public function donHang() { return $this->belongsTo(DonHang::class, 'ma_don_hang', 'ma_don_hang'); }
    public function mon() { return $this->belongsTo(Mon::class, 'ma_mon', 'ma_mon'); }
    public function kichCo() { return $this->belongsTo(KichCo::class, 'ma_kich_co', 'ma_kich_co'); }
    public function chiTietToppings() { return $this->hasMany(ChiTietTopping::class, 'ma_chi_tiet', 'ma_chi_tiet'); }
}
