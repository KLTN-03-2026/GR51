<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DanhGia extends Model {
    protected $table = 'danh_gias';
    protected $primaryKey = 'ma_danh_gia';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_danh_gia', 'ma_don_hang', 'so_sao', 'binh_luan'];
    
    public function donHang() { return $this->belongsTo(DonHang::class, 'ma_don_hang', 'ma_don_hang'); }
}
