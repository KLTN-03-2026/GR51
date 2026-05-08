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
        Schema::create('topping_cong_thucs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topping_id')->constrained('toppings')->onDelete('cascade');
            $table->foreignId('nguyen_lieu_id')->constrained('nguyen_lieus')->onDelete('cascade');
            $table->decimal('so_luong_can', 10, 2);
            $table->unique(['topping_id', 'nguyen_lieu_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topping_cong_thucs');
    }
};
