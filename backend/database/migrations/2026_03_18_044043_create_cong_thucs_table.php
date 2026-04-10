<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cong_thucs', function (Blueprint $table) {

            $table->string('ma_mon');
            $table->string('ma_nguyen_lieu');
            $table->decimal('so_luong_can', 10, 2);
            $table->primary(['ma_mon', 'ma_nguyen_lieu']);
            $table->foreign('ma_mon')->references('ma_mon')->on('mons');
            $table->foreign('ma_nguyen_lieu')->references('ma_nguyen_lieu')->on('nguyen_lieus');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cong_thucs');
    }
};
