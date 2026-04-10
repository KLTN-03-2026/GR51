<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chi_tiet_toppings', function (Blueprint $table) {
            // Thêm 2 cột mới vào sau cột ma_topping
            $table->integer('so_luong')->default(1)->after('ma_topping');

            // Dùng decimal hoặc double để lưu tiền tệ. 12 chữ số, 2 số thập phân.
            $table->decimal('gia_tien', 12, 2)->default(0)->after('so_luong');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chi_tiet_toppings', function (Blueprint $table) {
            // Nếu muốn rollback (lùi lại), nó sẽ tự xóa 2 cột này
            $table->dropColumn(['so_luong', 'gia_tien']);
        });
    }
};
