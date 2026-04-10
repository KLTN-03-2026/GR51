<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mons', function (Blueprint $table) {

            $table->string('ma_mon')->primary();
            $table->string('ma_danh_muc');
            $table->string('ten_mon');
            $table->string('hinh_anh')->nullable();
            $table->decimal('gia_ban', 15, 2);
            $table->text('cong_thuc')->nullable();
            $table->string('trang_thai');
            $table->foreign('ma_danh_muc')->references('ma_danh_muc')->on('danh_mucs');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('mons');
    }
};
