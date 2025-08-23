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
        Schema::table('daily_weights', function (Blueprint $table) {
            $table->boolean('exclude_from_dataset')->default(false)->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_weights', function (Blueprint $table) {
            $table->dropColumn('exclude_from_dataset');
        });
    }
};
