<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('khu_vucs', function (Blueprint $table) {
            $table->id();
            $table->string('ma_khu_vuc')->unique();
            $table->string('ten_khu_vuc');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('khu_vucs');
    }
};
