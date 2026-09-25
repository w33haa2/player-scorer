<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Existing players keep showing the name they had, now as their blader name.
     */
    public function up(): void
    {
        DB::table('players')
            ->whereNull('blader_name')
            ->update(['blader_name' => DB::raw('name')]);
    }

    /**
     * Reverse the migrations.
     *
     * Nothing to undo: the previous migration's rollback drops the column.
     */
    public function down(): void
    {
        //
    }
};
