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
        Schema::table('food', function (Blueprint $table) {
            // Add servings field - user can specify either servings or grams
            $table->decimal('servings', 8, 2)->nullable()->after('food_type_id');
            // Make quantity_grams nullable since user might specify servings instead
            $table->decimal('quantity_grams', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food', function (Blueprint $table) {
            $table->dropColumn('servings');
            $table->decimal('quantity_grams', 8, 2)->nullable(false)->change();
        });
    }
};
