<?php

namespace App\Services;

use App\Models\PlayerScore;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * The tournament's days, derived from when scores were recorded.
 *
 * Day 1 is the first local calendar date (tournament timezone) with a
 * recorded score, Day 2 the next date with scores, and so on. A score's day
 * comes from its `created_at`, so editing a score never moves it.
 */
class TournamentDays
{
    public function timezone(): string
    {
        return (string) config('app.tournament_timezone', 'Asia/Manila');
    }

    /**
     * Every day with at least one score, in order.
     *
     * @return list<TournamentDay>
     */
    public function all(): array
    {
        return once(function (): array {
            $timezone = $this->timezone();
            $storedIn = (string) config('app.timezone');

            $dates = PlayerScore::query()
                ->toBase()
                ->whereNotNull('created_at')
                ->distinct()
                ->pluck('created_at')
                ->map(fn (mixed $moment): string => CarbonImmutable::parse((string) $moment, $storedIn)
                    ->setTimezone($timezone)
                    ->toDateString())
                ->unique()
                ->sort()
                ->values()
                ->all();

            return array_map(
                fn (string $date, int $index): TournamentDay => new TournamentDay(
                    $index + 1,
                    CarbonImmutable::parse($date, $timezone),
                ),
                $dates,
                array_keys($dates),
            );
        });
    }

    public function find(int $number): ?TournamentDay
    {
        return $number > 0 ? ($this->all()[$number - 1] ?? null) : null;
    }

    /**
     * The day currently being aggregated: the latest day with scores.
     */
    public function latest(): ?TournamentDay
    {
        $days = $this->all();

        return $days === [] ? null : $days[count($days) - 1];
    }

    /**
     * The day a moment falls on, if it's a tournament day.
     */
    public function dayOf(DateTimeInterface $moment): ?TournamentDay
    {
        $date = CarbonImmutable::instance($moment)->setTimezone($this->timezone())->toDateString();

        foreach ($this->all() as $day) {
            if ($day->date->toDateString() === $date) {
                return $day;
            }
        }

        return null;
    }
}
