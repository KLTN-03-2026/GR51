<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chi_tiet_toppings', function (Blueprint $table) {
            $table->id();
            $table->string('ma_chi_tiet_topping')->unique();
            $table->foreignId('chi_tiet_don_hang_id')->constrained('chi_tiet_don_hangs')->onDelete('cascade');
            $table->foreignId('topping_id')->constrained('toppings')->onDelete('cascade');
            $table->integer('so_luong')->default(1);
            $table->decimal('gia_tien', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('chi_tiet_toppings');
    }
};
