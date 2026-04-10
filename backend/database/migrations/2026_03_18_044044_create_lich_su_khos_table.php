<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lich_su_khos', function (Blueprint $table) {

            $table->string('ma_ls_kho')->primary();
            $table->string('ma_nguyen_lieu');
            $table->string('ma_nhan_su')->nullable();
            $table->string('loai_giao_dich');
            $table->decimal('so_luong_thay_doi', 15, 2);
            $table->foreign('ma_nguyen_lieu')->references('ma_nguyen_lieu')->on('nguyen_lieus');
            $table->foreign('ma_nhan_su')->references('ma_nhan_su')->on('nhan_sus');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lich_su_khos');
    }
};
