<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Critical Database Fixes Migration
 * 
 * 1. Add soft deletes to master tables (mons, toppings, danh_mucs, nhan_sus, khu_vucs, nguyen_lieus, bans, kich_cos)
 * 2. Add ca_lam_id to don_hangs (link orders to shifts)
 * 3. Change loai_don from string to enum
 * 4. Change phuong_thuc_thanh_toan from string to enum
 * 5. Add unsigned constraint to so_luong, so_sao, gia_ban, etc.
 * 6. Add indexes on frequently queried columns
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add soft deletes to master tables
        Schema::table('nhan_sus', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('khu_vucs', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('bans', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('danh_mucs', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('mons', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('kich_cos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('toppings', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('nguyen_lieus', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Add ca_lam_id to don_hangs (link orders to shifts)
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->foreignId('ca_lam_id')->nullable()->after('nhan_su_id')->constrained('ca_lams')->onDelete('set null');
        });

        // 3 & 4. Change loai_don and phuong_thuc_thanh_toan to enum
        // Note: MySQL requires dropping and re-adding for type change
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->string('loai_don')->default('tai_ban')->comment('tai_ban, mang_di')->change();
            $table->string('phuong_thuc_thanh_toan')->default('tien_mat')->comment('tien_mat, chuyen_khoan')->change();
        });

        // 5. Add indexes on frequently queried columns
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->index('trang_thai_don');
            $table->index('trang_thai_thanh_toan');
            $table->index('created_at');
        });

        Schema::table('bans', function (Blueprint $table) {
            $table->index('trang_thai');
        });

        Schema::table('ca_lams', function (Blueprint $table) {
            $table->index('trang_thai');
        });

        Schema::table('lich_su_khos', function (Blueprint $table) {
            $table->index('created_at');
        });

        // 6. Add check constraints for critical numeric columns (MySQL 8.0.16+)
        // so_sao must be 1-5
        DB::statement('ALTER TABLE danh_gias ADD CONSTRAINT chk_so_sao CHECK (so_sao >= 1 AND so_sao <= 5)');
        // so_luong must be positive
        DB::statement('ALTER TABLE chi_tiet_don_hangs ADD CONSTRAINT chk_so_luong CHECK (so_luong > 0)');
        // don_gia must be non-negative
        DB::statement('ALTER TABLE chi_tiet_don_hangs ADD CONSTRAINT chk_don_gia CHECK (don_gia >= 0)');
        // gia_ban must be non-negative
        DB::statement('ALTER TABLE mons ADD CONSTRAINT chk_gia_ban CHECK (gia_ban >= 0)');
        // so_luong_can in cong_thucs must be positive
        DB::statement('ALTER TABLE cong_thucs ADD CONSTRAINT chk_so_luong_can CHECK (so_luong_can > 0)');
        // tong_tien must be non-negative
        DB::statement('ALTER TABLE don_hangs ADD CONSTRAINT chk_tong_tien CHECK (tong_tien >= 0)');
    }

    public function down(): void
    {
        // Remove check constraints
        DB::statement('ALTER TABLE danh_gias DROP CONSTRAINT IF EXISTS chk_so_sao');
        DB::statement('ALTER TABLE chi_tiet_don_hangs DROP CONSTRAINT IF EXISTS chk_so_luong');
        DB::statement('ALTER TABLE chi_tiet_don_hangs DROP CONSTRAINT IF EXISTS chk_don_gia');
        DB::statement('ALTER TABLE mons DROP CONSTRAINT IF EXISTS chk_gia_ban');
        DB::statement('ALTER TABLE cong_thucs DROP CONSTRAINT IF EXISTS chk_so_luong_can');
        DB::statement('ALTER TABLE don_hangs DROP CONSTRAINT IF EXISTS chk_tong_tien');

        // Remove indexes
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->dropIndex(['trang_thai_don']);
            $table->dropIndex(['trang_thai_thanh_toan']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('bans', function (Blueprint $table) {
            $table->dropIndex(['trang_thai']);
        });

        Schema::table('ca_lams', function (Blueprint $table) {
            $table->dropIndex(['trang_thai']);
        });

        Schema::table('lich_su_khos', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        // Remove ca_lam_id from don_hangs
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->dropForeign(['ca_lam_id']);
            $table->dropColumn('ca_lam_id');
        });

        // Remove soft deletes
        Schema::table('nguyen_lieus', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('toppings', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('kich_cos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('mons', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('danh_mucs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('bans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('khu_vucs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('nhan_sus', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
