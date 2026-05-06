<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhuVuc extends Model
{
    protected $table = 'khu_vucs';
    protected $fillable = ['ma_khu_vuc', 'ten_khu_vuc'];

    public function bans()
    {
        return $this->hasMany(Ban::class, 'khu_vuc_id');
    }
}
