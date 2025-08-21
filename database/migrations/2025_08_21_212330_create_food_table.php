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
        Schema::create('food', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_grams', 8, 2);
            $table->decimal('total_calories', 8, 2);
            $table->decimal('total_protein', 8, 2);
            $table->decimal('total_carbs', 8, 2);
            $table->decimal('total_fat', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamp('consumed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food');
    }
};
