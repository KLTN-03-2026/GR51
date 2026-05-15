<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ban extends Model
{
    use SoftDeletes;

    protected $table = 'bans';
    protected $fillable = ['ma_ban', 'ten_ban', 'khu_vuc_id', 'ma_qr', 'trang_thai'];

    public function khuVuc()
    {
        return $this->belongsTo(KhuVuc::class, 'khu_vuc_id');
    }

    public function donHangs()
    {
        return $this->hasMany(DonHang::class, 'ban_id');
    }
}
