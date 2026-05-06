<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class NhanSu extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nhan_sus';
    protected $hidden = ['mat_khau', 'ma_pin'];
    protected $fillable = ['ma_nhan_su', 'ten_dang_nhap', 'mat_khau', 'ma_pin', 'ho_ten', 'so_dien_thoai', 'vai_tro', 'trang_thai'];

    public function caLams()
    {
        return $this->hasMany(CaLam::class, 'nhan_su_id');
    }

    public function donHangs()
    {
        return $this->hasMany(DonHang::class, 'nhan_su_id');
    }

    public function lichSuKhos()
    {
        return $this->hasMany(LichSuKho::class, 'nhan_su_id');
    }

    public function aiChatHistories()
    {
        return $this->hasMany(AIChatHistory::class, 'nhan_su_id');
    }
}
