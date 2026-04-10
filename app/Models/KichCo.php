<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class KichCo extends Model {
    protected $table = 'kich_cos';
    protected $primaryKey = 'ma_kich_co';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_kich_co', 'ten_kich_co', 'gia_cong_them'];
    
    public function chiTietDonHangs() { return $this->hasMany(ChiTietDonHang::class, 'ma_kich_co', 'ma_kich_co'); }
}
