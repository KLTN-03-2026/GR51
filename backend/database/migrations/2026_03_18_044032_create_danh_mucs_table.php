<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('danh_mucs', function (Blueprint $table) {

            $table->string('ma_danh_muc')->primary();
            $table->string('ten_danh_muc');
                    $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('danh_mucs');
    }
};
