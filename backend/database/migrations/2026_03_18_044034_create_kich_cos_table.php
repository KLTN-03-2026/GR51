<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kich_cos', function (Blueprint $table) {
            $table->id();
            $table->string('ma_kich_co')->unique();
            $table->string('ten_kich_co');
            $table->decimal('gia_cong_them', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('kich_cos');
    }
};
