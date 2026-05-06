<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('nguyen_lieus', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nguyen_lieu')->unique();
            $table->string('ten_nguyen_lieu');
            $table->string('hinh_anh')->nullable();
            $table->string('don_vi_tinh');
            $table->decimal('ton_kho', 15, 2);
            $table->decimal('muc_canh_bao', 15, 2);
            $table->tinyInteger('trang_thai')->default(1)->comment('1: Còn hàng, 0: Hết hàng');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('nguyen_lieus');
    }
};
