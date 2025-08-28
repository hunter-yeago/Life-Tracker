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
        Schema::table('workout_types', function (Blueprint $table) {
            $table->enum('sides', ['both', 'left_right'])->default('both')->after('equipment_needed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workout_types', function (Blueprint $table) {
            $table->dropColumn('sides');
        });
    }
};
