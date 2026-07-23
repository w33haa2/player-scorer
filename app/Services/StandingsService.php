<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class StandingsService
{
    /**
     * Compute the current award standings.
     *
     * Each award follows a distinct ruling described in the tournament docs.
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
        return [
            $this->award(
                key: 'finals_mvp',
                name: 'Finals MVP',
                description: 'Highest accumulated score across all games.',
                metric: 'points',
                winner: $this->topBySum(),
            ),
            $this->award(
                key: 'rookie_of_the_season',
                name: 'Rookie of the Season',
                description: 'Best newcomer in the scene.',
                metric: 'points',
                winner: $this->rookie(),
            ),
            $this->award(
                key: 'stamina_king',
                name: 'Stamina King',
                description: 'Most spin finishes by a player.',
                metric: 'spin finishes',
                winner: $this->topByCount(fn (Builder $query) => $query->where('score', 1)),
            ),
            $this->award(
                key: 'over_lord',
                name: 'Over Lord',
                description: 'Most over finishes by a player.',
                metric: 'over finishes',
                winner: $this->topByCount(fn (Builder $query) => $query->where('score', 2)),
            ),
            $this->award(
                key: 'extreme_champion',
                name: 'Extreme Champion',
                description: 'Most extreme finishes by a player.',
                metric: 'extreme finishes',
                winner: $this->topByCount(fn (Builder $query) => $query->where('score', 3)),
            ),
            $this->award(
                key: 'burst_god',
                name: 'Burst God',
                description: 'Most burst finishes by a player.',
                metric: 'burst finishes',
                winner: $this->topByCount(fn (Builder $query) => $query->where('score', 2)->where('is_burst', true)),
            ),
        ];
    }

    /**
     * Assemble a single award result row.
     *
     * @param  array{player_id: int, player_name: string, value: int}|null  $winner
     * @return array{key: string, name: string, description: string, metric: string, winner: array{player_id: int, player_name: string, value: int}|null}
     */
    protected function award(string $key, string $name, string $description, string $metric, ?array $winner): array
    {
        return [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'metric' => $metric,
            'winner' => $winner,
        ];
    }

    /**
     * The player with the highest total score (Finals MVP).
     *
     * @return array{player_id: int, player_name: string, value: int}|null
     */
    protected function topBySum(): ?array
    {
        $row = DB::table('player_scores')
            ->join('players', 'players.id', '=', 'player_scores.player_id')
            ->select('players.id as player_id', 'players.name as player_name', DB::raw('SUM(player_scores.score) as value'))
            ->groupBy('players.id', 'players.name')
            ->orderByDesc('value')
            ->orderBy('players.id')
            ->first();

        return $this->normalize($row);
    }

    /**
     * The player with the most recent start date who has scored points.
     *
     * Returns null when no player has a start date recorded yet.
     *
     * @return array{player_id: int, player_name: string, value: int}|null
     */
    protected function rookie(): ?array
    {
        $row = DB::table('players')
            ->join('player_scores', 'player_scores.player_id', '=', 'players.id')
            ->whereNotNull('players.date_started')
            ->select('players.id as player_id', 'players.name as player_name', DB::raw('SUM(player_scores.score) as value'))
            ->groupBy('players.id', 'players.name', 'players.date_started')
            ->havingRaw('SUM(player_scores.score) > 0')
            ->orderByDesc('players.date_started')
            ->orderByDesc('value')
            ->orderBy('players.id')
            ->first();

        return $this->normalize($row);
    }

    /**
     * The player with the most entries matching the given filter.
     *
     * @param  callable(Builder): Builder  $filter
     * @return array{player_id: int, player_name: string, value: int}|null
     */
    protected function topByCount(callable $filter): ?array
    {
        $query = DB::table('player_scores')
            ->join('players', 'players.id', '=', 'player_scores.player_id')
            ->select('players.id as player_id', 'players.name as player_name', DB::raw('COUNT(*) as value'))
            ->groupBy('players.id', 'players.name')
            ->orderByDesc('value')
            ->orderBy('players.id');

        $filter($query);

        return $this->normalize($query->first());
    }

    /**
     * Normalize a raw query row into the winner shape.
     *
     * @return array{player_id: int, player_name: string, value: int}|null
     */
    protected function normalize(?object $row): ?array
    {
        if ($row === null) {
            return null;
        }

        /** @var array{player_id: int|string, player_name: string, value: int|string} $data */
        $data = (array) $row;

        if ((int) $data['value'] === 0) {
            return null;
        }

        return [
            'player_id' => (int) $data['player_id'],
            'player_name' => (string) $data['player_name'],
            'value' => (int) $data['value'],
        ];
    }
}
