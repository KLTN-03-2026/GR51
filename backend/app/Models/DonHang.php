<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DonHang extends Model {
    protected $table = 'don_hangs';
    protected $primaryKey = 'ma_don_hang';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_don_hang', 'ma_ban', 'ma_nhan_su', 'loai_don', 'tong_tien', 'phuong_thuc_thanh_toan', 'trang_thai_thanh_toan', 'trang_thai_don'];
    
    public function ban() { return $this->belongsTo(Ban::class, 'ma_ban', 'ma_ban'); }
    public function nhanSu() { return $this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su'); }
    public function chiTietDonHangs() { return $this->hasMany(ChiTietDonHang::class, 'ma_don_hang', 'ma_don_hang'); }
    public function danhGia() { return $this->hasOne(DanhGia::class, 'ma_don_hang', 'ma_don_hang'); }
}
