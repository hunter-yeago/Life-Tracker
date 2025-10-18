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
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->integer('set_number'); // 1, 2, 3, etc.
            $table->integer('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('duration_seconds')->nullable(); // for timed exercises
            $table->enum('difficulty', ['easy', 'hard', 'really_hard', 'almost_fail', 'fail'])->nullable();
            $table->boolean('completed')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'set_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
