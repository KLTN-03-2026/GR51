<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix #19: Thêm unique constraint cho don_hang_id trong bảng danh_gias.
 * 
 * Mỗi đơn hàng chỉ được đánh giá 1 lần. Controller đã check bằng PHP
 * nhưng thiếu DB-level constraint gây race condition tiềm ẩn.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('danh_gias', function (Blueprint $table) {
            // Đảm bảo mỗi đơn hàng chỉ có 1 đánh giá
            $table->unique('don_hang_id', 'danh_gias_don_hang_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('danh_gias', function (Blueprint $table) {
            $table->dropUnique('danh_gias_don_hang_id_unique');
        });
    }
};
