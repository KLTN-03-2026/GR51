<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {

            $table->string('ma_chi_tiet')->primary();
            $table->string('ma_don_hang');
            $table->string('ma_mon');
            $table->string('ma_kich_co')->nullable();
            $table->integer('so_luong');
            $table->string('ghi_chu')->nullable();
            $table->decimal('don_gia', 15, 2);
            $table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hangs');
            $table->foreign('ma_mon')->references('ma_mon')->on('mons');
            $table->foreign('ma_kich_co')->references('ma_kich_co')->on('kich_cos');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('chi_tiet_don_hangs');
    }
};
