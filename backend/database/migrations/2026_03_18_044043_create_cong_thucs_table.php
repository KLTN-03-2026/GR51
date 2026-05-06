<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cong_thucs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mon_id')->constrained('mons')->onDelete('cascade');
            $table->foreignId('nguyen_lieu_id')->constrained('nguyen_lieus')->onDelete('cascade');
            $table->decimal('so_luong_can', 10, 2);
            $table->unique(['mon_id', 'nguyen_lieu_id']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cong_thucs');
    }
};
