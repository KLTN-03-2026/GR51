<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bans', function (Blueprint $table) {
            $table->id();
            $table->string('ma_ban')->unique();
            $table->string('ten_ban');
            $table->foreignId('khu_vuc_id')->constrained('khu_vucs')->onDelete('cascade');
            $table->string('ma_qr')->unique();
            $table->tinyInteger('trang_thai')->default(1)->comment('1: Trống, 2: Có khách, 0: Đang bảo trì');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bans');
    }
};
