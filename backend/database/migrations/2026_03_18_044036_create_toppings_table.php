<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('toppings', function (Blueprint $table) {
            $table->id();
            $table->string('ma_topping')->unique();
            $table->string('ten_topping');
            $table->string('hinh_anh')->nullable();
            $table->decimal('gia_tien', 15, 2);
            $table->tinyInteger('trang_thai')->default(1)->comment('1: Còn hàng, 0: Hết hàng');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('toppings');
    }
};
