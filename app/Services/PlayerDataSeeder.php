<?php

namespace App\Services;

use App\Models\ActionLog;
use App\Models\DataSeed;
use App\Models\Player;
use App\Models\PlayerScore;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the player list with the DBBL roster (database/data/dbbl-roster.php).
 *
 * This is a reset: every existing player, their battles and the score audit
 * log are deleted first. It can only run once; the `data_seeds` row with a
 * unique name is claimed inside the same transaction, so a second (or
 * concurrent) attempt fails and rolls back without touching anything.
 */
class PlayerDataSeeder
{
    public const NAME = 'dbbl-roster';

    /**
     * The roster to seed, grouped by team.
     *
     * @return list<array{name: string, acronym: string|null, logo: string|null, players: list<array{name: string|null, blader_name: string}>}>
     */
    public function roster(): array
    {
        return require database_path('data/dbbl-roster.php');
    }

    /**
     * The record of the seed, if it has already run.
     */
    public function record(): ?DataSeed
    {
        return DataSeed::query()->with('user:id,name')->firstWhere('name', self::NAME);
    }

    public function hasRun(): bool
    {
        return DataSeed::query()->where('name', self::NAME)->exists();
    }

    /**
     * Wipe the current players and load the roster.
     *
     * @return array{teams: int, players: int}
     *
     * @throws UniqueConstraintViolationException When it has already run.
     */
    public function seed(User $user): array
    {
        $roster = $this->roster();

        return DB::transaction(function () use ($roster, $user): array {
            // Claim the one-time seed first so a repeat attempt fails before any delete.
            DataSeed::create(['name' => self::NAME, 'user_id' => $user->id]);

            // Bulk deletes skip model observers, so no audit entries are written.
            ActionLog::query()->delete();
            PlayerScore::query()->delete();
            TeamMember::query()->delete();
            Player::query()->delete();
            Team::query()->delete();

            $players = 0;

            foreach ($roster as $entry) {
                $team = Team::create([
                    'name' => $entry['name'],
                    'acronym' => $entry['acronym'],
                    'logo_path' => $entry['logo'],
                ]);

                foreach ($entry['players'] as $player) {
                    $team->players()->attach(Player::create($player));
                    $players++;
                }
            }

            return ['teams' => count($roster), 'players' => $players];
        });
    }
}
