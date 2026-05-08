                1 1< 51 ?php

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
                    Schema::create('mon_kich_co', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('mon_id')->constrained('mons')->onDelete('cascade');
                    $table->foreignId('kich_co_id')->constrained('kich_cos')->onDelete('cascade');
                    $table->timestamps();
                    });
                    }

                    /**
                    * Reverse the migrations.
                    */
                    public function down(): void
                    {
                    Schema::dropIfExists('mon_kich_co');
                    }
                    };