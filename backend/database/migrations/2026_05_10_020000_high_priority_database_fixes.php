<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * High Priority Database Fixes
 * 
 * 1. Remove redundant 'cong_thuc' text column from 'mons' (bảng cong_thucs đã có)
 * 2. Add 'gia_cong_them' to 'mon_kich_co' pivot (giá phụ thu riêng theo từng món)
 * 3. Add UNIQUE constraints to pivot tables (mon_topping, mon_kich_co)
 * 4. Fix decimal precision inconsistency in chi_tiet_toppings (12,2 → 15,2)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove redundant 'cong_thuc' text column from mons
        // (Bảng cong_thucs đã lưu công thức chi tiết theo nguyên liệu)
        Schema::table('mons', function (Blueprint $table) {
            $table->dropColumn('cong_thuc');
        });

        // 2. Add 'gia_cong_them' to mon_kich_co pivot
        // Cho phép mỗi món có giá phụ thu riêng cho từng size
        // Nếu NULL → fallback về kich_cos.gia_cong_them (global)
        Schema::table('mon_kich_co', function (Blueprint $table) {
            $table->decimal('gia_cong_them', 15, 2)->nullable()->after('kich_co_id')
                  ->comment('Giá riêng theo món, NULL = dùng giá global từ kich_cos');
        });

        // 3. Add UNIQUE constraints to pivot tables
        Schema::table('mon_topping', function (Blueprint $table) {
            $table->unique(['mon_id', 'topping_id']);
        });

        Schema::table('mon_kich_co', function (Blueprint $table) {
            $table->unique(['mon_id', 'kich_co_id']);
        });

        // 4. Fix decimal precision in chi_tiet_toppings (12,2 → 15,2)
        Schema::table('chi_tiet_toppings', function (Blueprint $table) {
            $table->decimal('gia_tien', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        // Reverse decimal precision
        Schema::table('chi_tiet_toppings', function (Blueprint $table) {
            $table->decimal('gia_tien', 12, 2)->default(0)->change();
        });

        // Remove UNIQUE constraints
        Schema::table('mon_kich_co', function (Blueprint $table) {
            $table->dropUnique(['mon_id', 'kich_co_id']);
        });

        Schema::table('mon_topping', function (Blueprint $table) {
            $table->dropUnique(['mon_id', 'topping_id']);
        });

        // Remove gia_cong_them from pivot
        Schema::table('mon_kich_co', function (Blueprint $table) {
            $table->dropColumn('gia_cong_them');
        });

        // Restore cong_thuc column
        Schema::table('mons', function (Blueprint $table) {
            $table->text('cong_thuc')->nullable()->after('gia_ban');
        });
    }
};
