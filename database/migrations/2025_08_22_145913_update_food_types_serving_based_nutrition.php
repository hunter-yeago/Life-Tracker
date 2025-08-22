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
        Schema::table('food_types', function (Blueprint $table) {
            // Add serving-based nutrition fields
            $table->string('serving_size')->nullable()->after('description'); // e.g., "1 cup", "1 serving", "1 can"
            $table->decimal('serving_weight_grams', 8, 2)->nullable()->after('serving_size'); // Weight of one serving in grams

            // Rename existing per-100g fields to per-serving fields
            $table->renameColumn('calories_per_100g', 'calories_per_serving');
            $table->renameColumn('protein_per_100g', 'protein_per_serving');
            $table->renameColumn('carbs_per_100g', 'carbs_per_serving');
            $table->renameColumn('fat_per_100g', 'fat_per_serving');

            // Add flag for one-time items
            $table->boolean('is_one_time_item')->default(false)->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_types', function (Blueprint $table) {
            // Remove new fields
            $table->dropColumn(['serving_size', 'serving_weight_grams', 'is_one_time_item']);

            // Rename columns back
            $table->renameColumn('calories_per_serving', 'calories_per_100g');
            $table->renameColumn('protein_per_serving', 'protein_per_100g');
            $table->renameColumn('carbs_per_serving', 'carbs_per_100g');
            $table->renameColumn('fat_per_serving', 'fat_per_100g');
        });
    }
};
