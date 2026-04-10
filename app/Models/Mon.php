<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Mon extends Model {
    protected $table = 'mons';
    protected $primaryKey = 'ma_mon';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_mon', 'ma_danh_muc', 'ten_mon', 'hinh_anh', 'gia_ban', 'cong_thuc', 'trang_thai'];
    
    public function danhMuc() { return $this->belongsTo(DanhMuc::class, 'ma_danh_muc', 'ma_danh_muc'); }
    public function chiTietDonHangs() { return $this->hasMany(ChiTietDonHang::class, 'ma_mon', 'ma_mon'); }
    public function congThucs() { return $this->hasMany(CongThuc::class, 'ma_mon', 'ma_mon'); }
}
