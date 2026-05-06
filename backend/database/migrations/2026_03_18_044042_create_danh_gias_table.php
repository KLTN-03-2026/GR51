<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('danh_gias', function (Blueprint $table) {
            $table->id();
            $table->string('ma_danh_gia')->unique();
            $table->foreignId('don_hang_id')->constrained('don_hangs')->onDelete('cascade');
            $table->integer('so_sao');
            $table->text('binh_luan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('danh_gias');
    }
};
