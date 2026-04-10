<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('don_hangs', function (Blueprint $table) {

            $table->string('ma_don_hang')->primary();
            $table->string('ma_ban')->nullable();
            $table->string('ma_nhan_su')->nullable();
            $table->string('loai_don');
            $table->decimal('tong_tien', 15, 2);
            $table->string('phuong_thuc_thanh_toan');
            $table->string('trang_thai_thanh_toan');
            $table->string('trang_thai_don');
            $table->foreign('ma_ban')->references('ma_ban')->on('bans');
            $table->foreign('ma_nhan_su')->references('ma_nhan_su')->on('nhan_sus');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('don_hangs');
    }
};
