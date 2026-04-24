<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIChatHistory extends Model
{
    protected $table = 'ai_chat_histories';

    protected $fillable = [
        'ma_nhan_su',
        'session_id',
        'role',
        'content',
    ];

    public function nhanSu()
    {
        return $this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su');
    }
}
