<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('toppings', function (Blueprint $table) {

            $table->string('ma_topping')->primary();
            $table->string('ten_topping');
            $table->string('hinh_anh')->nullable();
            $table->decimal('gia_tien', 15, 2);
            $table->string('trang_thai');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('toppings');
    }
};
