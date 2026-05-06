<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lich_su_khos', function (Blueprint $table) {
            $table->id();
            $table->string('ma_ls_kho')->unique();
            $table->foreignId('nguyen_lieu_id')->constrained('nguyen_lieus')->onDelete('cascade');
            $table->foreignId('nhan_su_id')->nullable()->constrained('nhan_sus')->onDelete('set null');
            $table->tinyInteger('loai_giao_dich')->comment('1: Nhập, 2: Xuất, 3: Điều chỉnh');
            $table->decimal('so_luong_thay_doi', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lich_su_khos');
    }
};
