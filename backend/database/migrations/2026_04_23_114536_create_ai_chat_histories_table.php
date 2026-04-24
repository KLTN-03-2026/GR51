<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_histories', function (Blueprint $table) {
            $table->id();
            $table->string('ma_nhan_su', 20);
            $table->string('session_id', 64)->index();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->timestamps();

            $table->foreign('ma_nhan_su')
                  ->references('ma_nhan_su')
                  ->on('nhan_sus')
                  ->onDelete('cascade');

            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_histories');
    }
};
