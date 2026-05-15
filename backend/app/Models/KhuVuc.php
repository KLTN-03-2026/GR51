<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KhuVuc extends Model
{
    use SoftDeletes;

    protected $table = 'khu_vucs';
    protected $fillable = ['ma_khu_vuc', 'ten_khu_vuc'];

    public function bans()
    {
        return $this->hasMany(Ban::class, 'khu_vuc_id');
    }
}
