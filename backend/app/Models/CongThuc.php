<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class CongThuc extends Model {
    protected $table = 'cong_thucs';
    public $incrementing = false;
    protected $fillable = ['ma_mon', 'ma_nguyen_lieu', 'so_luong_can'];
    
    public function mon() { return $this->belongsTo(Mon::class, 'ma_mon', 'ma_mon'); }
    public function nguyenLieu() { return $this->belongsTo(NguyenLieu::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
    
    // For composite primary key support in eloquent we just disable auto-incrementing
    // and avoid using find() without custom queries.
}
