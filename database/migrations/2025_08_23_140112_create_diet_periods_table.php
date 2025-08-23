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
        Schema::create('diet_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null means ongoing
            $table->enum('phase_type', ['cut', 'bulk', 'maintenance']);
            $table->string('name'); // e.g., "Summer Cut 2025", "Winter Bulk"
            $table->text('notes')->nullable();
            $table->decimal('target_calories', 8, 2)->nullable();
            $table->decimal('target_protein', 8, 2)->nullable();
            $table->decimal('target_carbs', 8, 2)->nullable();
            $table->decimal('target_fat', 8, 2)->nullable();
            $table->decimal('starting_weight', 8, 2)->nullable();
            $table->decimal('target_weight', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_date']);
            $table->index(['user_id', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diet_periods');
    }
};
