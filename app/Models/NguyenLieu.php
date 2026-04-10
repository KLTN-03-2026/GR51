<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class NguyenLieu extends Model {
    protected $table = 'nguyen_lieus';
    protected $primaryKey = 'ma_nguyen_lieu';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_nguyen_lieu', 'ten_nguyen_lieu', 'hinh_anh', 'don_vi_tinh', 'ton_kho', 'muc_canh_bao', 'trang_thai'];
    
    public function congThucs() { return $this->hasMany(CongThuc::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
    public function lichSuKhos() { return $this->hasMany(LichSuKho::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
}
