<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Players are now shown by their blader name; the real name is optional.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('blader_name')->nullable()->after('name');
            $table->string('name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('players')->whereNull('name')->update(['name' => DB::raw('blader_name')]);

        Schema::table('players', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn('blader_name');
        });
    }
};
