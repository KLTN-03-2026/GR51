<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DanhMuc extends Model {
    protected $table = 'danh_mucs';
    protected $primaryKey = 'ma_danh_muc';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_danh_muc', 'ten_danh_muc'];
    
    public function mons() { return $this->hasMany(Mon::class, 'ma_danh_muc', 'ma_danh_muc'); }
}
