<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nhan_sus', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nhan_su')->unique();
            $table->string('ten_dang_nhap')->unique();
            $table->string('mat_khau')->nullable();
            $table->string('ma_pin')->nullable();
            $table->string('ho_ten');
            $table->string('so_dien_thoai');
            $table->string('vai_tro');
            $table->tinyInteger('trang_thai')->default(1)->comment('1: Active, 0: Inactive');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhan_sus');
    }
};
