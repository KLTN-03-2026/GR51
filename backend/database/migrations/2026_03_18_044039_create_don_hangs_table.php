<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->id();
            $table->string('ma_don_hang')->unique();
            $table->foreignId('ban_id')->nullable()->constrained('bans')->onDelete('set null');
            $table->foreignId('nhan_su_id')->nullable()->constrained('nhan_sus')->onDelete('set null');
            $table->string('loai_don');
            $table->decimal('tong_tien', 15, 2);
            $table->string('phuong_thuc_thanh_toan');
            $table->tinyInteger('trang_thai_thanh_toan')->default(0)->comment('0: Chưa thanh toán, 1: Đã thanh toán');
            $table->tinyInteger('trang_thai_don')->default(0)->comment('0: Chờ xử lý, 1: Đang pha chế, 2: Hoàn thành, 3: Đã hủy');
            $table->text('ly_do_huy')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_hangs');
    }
};
