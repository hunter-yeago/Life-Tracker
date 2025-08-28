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
        Schema::table('workouts', function (Blueprint $table) {
            // Remove the old both_sides boolean column
            $table->dropColumn('both_sides');
            
            // Add separate columns for left and right sides
            $table->integer('left_sets')->nullable()->after('distance');
            $table->integer('left_reps')->nullable()->after('left_sets');
            $table->decimal('left_weight', 8, 2)->nullable()->after('left_reps');
            $table->enum('left_difficulty', ['easy', 'hard', 'really_hard', 'almost_fail', 'fail'])->nullable()->after('left_weight');
            
            $table->integer('right_sets')->nullable()->after('left_difficulty');
            $table->integer('right_reps')->nullable()->after('right_sets');
            $table->decimal('right_weight', 8, 2)->nullable()->after('right_reps');
            $table->enum('right_difficulty', ['easy', 'hard', 'really_hard', 'almost_fail', 'fail'])->nullable()->after('right_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Remove the new left/right columns
            $table->dropColumn([
                'left_sets', 'left_reps', 'left_weight', 'left_difficulty',
                'right_sets', 'right_reps', 'right_weight', 'right_difficulty'
            ]);
            
            // Restore the both_sides boolean column
            $table->boolean('both_sides')->default(true)->after('distance');
        });
    }
};
