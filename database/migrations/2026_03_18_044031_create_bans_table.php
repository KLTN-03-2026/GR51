<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bans', function (Blueprint $table) {

            $table->string('ma_ban')->primary();
            $table->string('ten_ban');
            $table->string('ma_khu_vuc');
            $table->string('ma_qr');
            $table->string('trang_thai');
            $table->foreign('ma_khu_vuc')->references('ma_khu_vuc')->on('khu_vucs');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('bans');
    }
};
