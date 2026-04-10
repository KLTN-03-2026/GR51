<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chi_tiet_toppings', function (Blueprint $table) {

            $table->string('ma_chi_tiet_topping')->primary();
            $table->string('ma_chi_tiet');
            $table->string('ma_topping');
            $table->foreign('ma_chi_tiet')->references('ma_chi_tiet')->on('chi_tiet_don_hangs');
            $table->foreign('ma_topping')->references('ma_topping')->on('toppings');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('chi_tiet_toppings');
    }
};
