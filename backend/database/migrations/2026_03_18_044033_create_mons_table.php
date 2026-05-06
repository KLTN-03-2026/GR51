<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mons', function (Blueprint $table) {
            $table->id();
            $table->string('ma_mon')->unique();
            $table->foreignId('danh_muc_id')->constrained('danh_mucs')->onDelete('cascade');
            $table->string('ten_mon');
            $table->string('hinh_anh')->nullable();
            $table->decimal('gia_ban', 15, 2);
            $table->text('cong_thuc')->nullable();
            $table->tinyInteger('trang_thai')->default(1)->comment('1: Có sẵn, 0: Tạm hết');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mons');
    }
};
