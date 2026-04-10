<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class KhuVuc extends Model {
    protected $table = 'khu_vucs';
    protected $primaryKey = 'ma_khu_vuc';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_khu_vuc', 'ten_khu_vuc'];
    
    public function bans() { return $this->hasMany(Ban::class, 'ma_khu_vuc', 'ma_khu_vuc'); }
}
