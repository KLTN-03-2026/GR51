<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ChiTietTopping extends Model {
    protected $table = 'chi_tiet_toppings';
    protected $primaryKey = 'ma_chi_tiet_topping';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_chi_tiet_topping', 'ma_chi_tiet', 'ma_topping', 'so_luong', 'gia_tien'];
    
    public function chiTietDonHang() { return $this->belongsTo(ChiTietDonHang::class, 'ma_chi_tiet', 'ma_chi_tiet'); }
    public function topping() { return $this->belongsTo(Topping::class, 'ma_topping', 'ma_topping'); }
}
