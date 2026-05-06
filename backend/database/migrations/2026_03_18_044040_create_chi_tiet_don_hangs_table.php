<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->id();
            $table->string('ma_chi_tiet')->unique();
            $table->foreignId('don_hang_id')->constrained('don_hangs')->onDelete('cascade');
            $table->foreignId('mon_id')->constrained('mons')->onDelete('cascade');
            $table->foreignId('kich_co_id')->nullable()->constrained('kich_cos')->onDelete('set null');
            $table->integer('so_luong');
            $table->string('ghi_chu')->nullable();
            $table->decimal('don_gia', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('chi_tiet_don_hangs');
    }
};
