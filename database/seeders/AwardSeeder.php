<?php

namespace Database\Seeders;

use App\Models\Award;
use Illuminate\Database\Seeder;

class AwardSeeder extends Seeder
{
    /**
     * The awards handed out for the tournament.
     *
     * @var list<string>
     */
    public const AWARDS = [
        'Finals MVP',
        'Rookie of the Season',
        'Stamina King',
        'Over Lord',
        'Extreme Champion',
        'Burst God',
    ];

    /**
     * Seed the awards table.
     */
    public function run(): void
    {
        foreach (self::AWARDS as $name) {
            Award::firstOrCreate(['name' => $name]);
        }
    }
}
