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
            // Remove duration_minutes field
            $table->dropColumn('duration_minutes');

            // Add new fields
            $table->boolean('both_sides')->default(true)->after('reps');
            $table->enum('difficulty', ['easy', 'hard', 'really_hard', 'almost_fail', 'fail'])->nullable()->after('both_sides');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Re-add duration_minutes field
            $table->integer('duration_minutes')->nullable()->after('weight');

            // Remove the new fields
            $table->dropColumn(['both_sides', 'difficulty']);
        });
    }
};
