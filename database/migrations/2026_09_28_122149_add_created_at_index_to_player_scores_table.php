<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-day standings filter scores by `created_at` ranges.
     */
    public function up(): void
    {
        Schema::table('player_scores', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_scores', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
