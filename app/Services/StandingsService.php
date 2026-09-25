<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerScore;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class StandingsService
{
    /**
     * Compute the current holder of each award.
     *
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     metric: string,
     *     winner: array{player_id: int, player_name: string, value: int}|null
     * }>
     */
    public function compute(): array
    {
        return array_map(function (array $award): array {
            $winner = $award['leaders'][0] ?? null;
            unset($award['leaders']);

            return [...$award, 'winner' => $winner];
        }, $this->leaderboards(1));
    }

    /**
     * Compute the top contenders for each award, best first.
     *
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     metric: string,
     *     leaders: list<array{player_id: int, player_name: string, value: int}>
     * }>
     */
    public function leaderboards(int $limit = 3): array
    {
        return [
            $this->award(
                key: 'finals_mvp',
                name: 'Finals MVP',
                description: 'Highest accumulated score across all games.',
                metric: 'points',
                leaders: $this->topBySum($limit),
            ),
            $this->award(
                key: 'rookie_of_the_season',
                name: 'Rookie of the Season',
                description: 'Best newcomer in the scene.',
                metric: 'points',
                leaders: $this->rookies($limit),
            ),
            $this->award(
                key: 'stamina_king',
                name: 'Stamina King',
                description: 'Most spin finishes by a player.',
                metric: 'spin finishes',
                leaders: $this->topByCount(fn (Builder $query) => $query->where('score', 1), $limit),
            ),
            $this->award(
                key: 'over_lord',
                name: 'Over Lord',
                description: 'Most over finishes by a player.',
                metric: 'over finishes',
                // Bursts also score 2, but they're a separate finish (Burst God).
                leaders: $this->topByCount(fn (Builder $query) => $query->where('score', 2)->where('is_burst', false), $limit),
            ),
            $this->award(
                key: 'extreme_champion',
                name: 'Extreme Champion',
                description: 'Most extreme finishes by a player.',
                metric: 'extreme finishes',
                leaders: $this->topByCount(fn (Builder $query) => $query->where('score', 3), $limit),
            ),
            $this->award(
                key: 'burst_god',
                name: 'Burst God',
                description: 'Most burst finishes by a player.',
                metric: 'burst finishes',
                leaders: $this->topByCount(fn (Builder $query) => $query->where('score', 2)->where('is_burst', true), $limit),
            ),
        ];
    }

    /**
     * Every player ranked by total points, with a finish-type breakdown.
     *
     * Uses standard competition ranking, so tied players share a rank
     * (1, 2, 2, 4). Players without any battles are listed last with zeros.
     *
     * @return list<array{player_id: int, player_name: string, rank: int, battles: int, points: int, spin: int, over: int, burst: int, extreme: int}>
     */
    public function playerLeaderboard(): array
    {
        // `CASE WHEN is_burst` uses the column itself as the condition, which
        // behaves the same on MySQL, PostgreSQL and SQLite (no bool binding).
        $rows = DB::table('players')
            ->leftJoin('player_scores', 'player_scores.player_id', '=', 'players.id')
            ->select('players.id as player_id', 'players.name as player_name')
            ->selectRaw('COUNT(player_scores.id) as battles')
            ->selectRaw('COALESCE(SUM(player_scores.score), 0) as points')
            ->selectRaw('SUM(CASE WHEN player_scores.score = 1 THEN 1 ELSE 0 END) as spin_count')
            ->selectRaw('SUM(CASE WHEN player_scores.score = 2 AND NOT player_scores.is_burst THEN 1 ELSE 0 END) as over_count')
            ->selectRaw('SUM(CASE WHEN player_scores.is_burst THEN 1 ELSE 0 END) as burst_count')
            ->selectRaw('SUM(CASE WHEN player_scores.score = 3 THEN 1 ELSE 0 END) as extreme_count')
            ->groupBy('players.id', 'players.name')
            ->orderByDesc('points')
            ->orderBy('players.name')
            ->get();

        $leaderboard = [];
        $rank = 0;
        $previousPoints = null;

        foreach ($rows->values() as $index => $row) {
            /** @var array{player_id: int|string, player_name: string, battles: int|string|null, points: int|string|null, spin_count: int|string|null, over_count: int|string|null, burst_count: int|string|null, extreme_count: int|string|null} $data */
            $data = (array) $row;
            $points = (int) $data['points'];

            if ($points !== $previousPoints) {
                $rank = $index + 1;
                $previousPoints = $points;
            }

            $leaderboard[] = [
                'player_id' => (int) $data['player_id'],
                'player_name' => (string) $data['player_name'],
                'rank' => $rank,
                'battles' => (int) $data['battles'],
                'points' => $points,
                'spin' => (int) $data['spin_count'],
                'over' => (int) $data['over_count'],
                'burst' => (int) $data['burst_count'],
                'extreme' => (int) $data['extreme_count'],
            ];
        }

        return $leaderboard;
    }

    /**
     * A player's stat card: overall rank, totals, finish breakdown, top-3
     * title placements and their most recent battles.
     *
     * @return array{
     *     player_id: int,
     *     player_name: string,
     *     date_started: string|null,
     *     rank: int|null,
     *     total_players: int,
     *     battles: int,
     *     points: int,
     *     average: float,
     *     breakdown: array{spin: int, over: int, burst: int, extreme: int},
     *     placements: list<array{key: string, name: string, position: int, value: int, metric: string}>,
     *     recent: list<array{id: int, score: int, is_burst: bool, created_at: string|null}>
     * }
     */
    public function playerProfile(Player $player, int $recentLimit = 10): array
    {
        $leaderboard = $this->playerLeaderboard();
        $row = collect($leaderboard)->firstWhere('player_id', $player->id);

        $battles = (int) ($row['battles'] ?? 0);
        $points = (int) ($row['points'] ?? 0);

        $placements = [];

        foreach ($this->leaderboards(3) as $award) {
            foreach ($award['leaders'] as $index => $leader) {
                if ($leader['player_id'] === $player->id) {
                    $placements[] = [
                        'key' => $award['key'],
                        'name' => $award['name'],
                        'position' => $index + 1,
                        'value' => $leader['value'],
                        'metric' => $award['metric'],
                    ];
                }
            }
        }

        usort($placements, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        $recent = array_values($player->scores()
            ->latest()
            ->orderByDesc('id')
            ->limit($recentLimit)
            ->get(['id', 'score', 'is_burst', 'created_at'])
            ->map(fn (PlayerScore $score): array => [
                'id' => $score->id,
                'score' => $score->score,
                'is_burst' => $score->is_burst,
                'created_at' => $score->created_at?->toIso8601String(),
            ])
            ->all());

        return [
            'player_id' => $player->id,
            'player_name' => $player->name,
            'date_started' => $player->date_started?->toDateString(),
            'rank' => $battles > 0 ? ($row['rank'] ?? null) : null,
            'total_players' => count($leaderboard),
            'battles' => $battles,
            'points' => $points,
            'average' => $battles > 0 ? round($points / $battles, 2) : 0.0,
            'breakdown' => [
                'spin' => (int) ($row['spin'] ?? 0),
                'over' => (int) ($row['over'] ?? 0),
                'burst' => (int) ($row['burst'] ?? 0),
                'extreme' => (int) ($row['extreme'] ?? 0),
            ],
            'placements' => $placements,
            'recent' => $recent,
        ];
    }

    /**
     * Assemble a single award row.
     *
     * @param  list<array{player_id: int, player_name: string, value: int}>  $leaders
     * @return array{key: string, name: string, description: string, metric: string, leaders: list<array{player_id: int, player_name: string, value: int}>}
     */
    protected function award(string $key, string $name, string $description, string $metric, array $leaders): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'metric' => $metric,
            'leaders' => $leaders,
        ];
    }

    /**
     * Players ranked by total score (Finals MVP).
     *
     * @return list<array{player_id: int, player_name: string, value: int}>
     */
    protected function topBySum(int $limit): array
    {
        $rows = DB::table('player_scores')
            ->join('players', 'players.id', '=', 'player_scores.player_id')
            ->select('players.id as player_id', 'players.name as player_name', DB::raw('SUM(player_scores.score) as value'))
            ->groupBy('players.id', 'players.name')
            ->orderByDesc('value')
            ->orderBy('players.id')
            ->limit($limit)
            ->get();

        return $this->normalize($rows->all());
    }

    /**
     * Players with the most recent start date who have scored points.
     *
     * Returns an empty list when no player has a start date recorded yet.
     *
     * @return list<array{player_id: int, player_name: string, value: int}>
     */
    protected function rookies(int $limit): array
    {
        $rows = DB::table('players')
            ->join('player_scores', 'player_scores.player_id', '=', 'players.id')
            ->whereNotNull('players.date_started')
            ->select('players.id as player_id', 'players.name as player_name', DB::raw('SUM(player_scores.score) as value'))
            ->groupBy('players.id', 'players.name', 'players.date_started')
            ->havingRaw('SUM(player_scores.score) > 0')
            ->orderByDesc('players.date_started')
            ->orderByDesc('value')
            ->orderBy('players.id')
            ->limit($limit)
            ->get();

        return $this->normalize($rows->all());
    }

    /**
     * Players ranked by the number of entries matching the given filter.
     *
     * @param  callable(Builder): Builder  $filter
     * @return list<array{player_id: int, player_name: string, value: int}>
     */
    protected function topByCount(callable $filter, int $limit): array
    {
        $query = DB::table('player_scores')
            ->join('players', 'players.id', '=', 'player_scores.player_id')
            ->select('players.id as player_id', 'players.name as player_name', DB::raw('COUNT(*) as value'))
            ->groupBy('players.id', 'players.name')
            ->orderByDesc('value')
            ->orderBy('players.id')
            ->limit($limit);

        $filter($query);

        return $this->normalize($query->get()->all());
    }

    /**
     * Normalize raw query rows, dropping any zero-value rows.
     *
     * @param  array<int, object>  $rows
     * @return list<array{player_id: int, player_name: string, value: int}>
     */
    protected function normalize(array $rows): array
    {
        $leaders = [];

        foreach ($rows as $row) {
            /** @var array{player_id: int|string, player_name: string, value: int|string} $data */
            $data = (array) $row;

            if ((int) $data['value'] === 0) {
                continue;
            }

            $leaders[] = [
                'player_id' => (int) $data['player_id'],
                'player_name' => (string) $data['player_name'],
                'value' => (int) $data['value'],
            ];
        }

        return $leaders;
    }
}
