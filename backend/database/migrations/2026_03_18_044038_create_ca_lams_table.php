<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ca_lams', function (Blueprint $table) {
            $table->id();
            $table->string('ma_ca_lam')->unique();
            $table->foreignId('nhan_su_id')->constrained('nhan_sus')->onDelete('cascade');
            $table->dateTime('thoi_gian_bat_dau');
            $table->dateTime('thoi_gian_ket_thuc')->nullable();
            $table->decimal('tien_mat_dau_ca', 15, 2);
            $table->decimal('tien_mat_he_thong', 15, 2);
            $table->decimal('tien_mat_thuc_te', 15, 2)->nullable();
            $table->decimal('tong_doanh_thu', 15, 2)->default(0);
            $table->text('ghi_chu')->nullable();
            $table->tinyInteger('trang_thai')->default(1)->comment('1: Đang làm, 0: Đã kết thúc');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('ca_lams');
    }
};
