<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Topping extends Model {
    protected $table = 'toppings';
    protected $primaryKey = 'ma_topping';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['ma_topping', 'ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai'];
    
    public function chiTietToppings() { return $this->hasMany(ChiTietTopping::class, 'ma_topping', 'ma_topping'); }
}
