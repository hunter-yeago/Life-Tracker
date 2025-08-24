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
        Schema::table('daily_data_exclusions', function (Blueprint $table) {
            $table->text('food_notes')->nullable();
            $table->text('workout_notes')->nullable();
            $table->text('weight_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_data_exclusions', function (Blueprint $table) {
            $table->dropColumn(['food_notes', 'workout_notes', 'weight_notes']);
        });
    }
};
