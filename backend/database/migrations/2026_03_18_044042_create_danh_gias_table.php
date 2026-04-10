<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('danh_gias', function (Blueprint $table) {

            $table->string('ma_danh_gia')->primary();
            $table->string('ma_don_hang');
            $table->integer('so_sao');
            $table->text('binh_luan')->nullable();
            $table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hangs');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('danh_gias');
    }
};
