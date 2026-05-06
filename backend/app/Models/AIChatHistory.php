<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIChatHistory extends Model
{
    protected $table = 'ai_chat_histories';

    protected $fillable = [
        'nhan_su_id',
        'session_id',
        'role',
        'content',
    ];

    public function nhanSu()
    {
        return $this->belongsTo(NhanSu::class, 'nhan_su_id');
    }
}
