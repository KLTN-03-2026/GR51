<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Ban extends Model {
    protected $table = 'bans';
    protected $primaryKey = 'ma_ban';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_ban', 'ten_ban', 'ma_khu_vuc', 'ma_qr', 'trang_thai'];
    
    public function khuVuc() { return $this->belongsTo(KhuVuc::class, 'ma_khu_vuc', 'ma_khu_vuc'); }
    public function donHangs() { return $this->hasMany(DonHang::class, 'ma_ban', 'ma_ban'); }
}
