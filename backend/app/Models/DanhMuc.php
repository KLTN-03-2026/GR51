<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DanhMuc extends Model
{
    protected $table = 'danh_mucs';
    protected $fillable = ['ma_danh_muc', 'ten_danh_muc'];

    public function mons()
    {
        return $this->hasMany(Mon::class, 'danh_muc_id');
    }
}
