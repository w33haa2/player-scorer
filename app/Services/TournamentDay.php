<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * One calendar day of the tournament ("Day 1", "Day 2", ...), in the
 * tournament's local timezone.
 */
final readonly class TournamentDay
{
    /**
     * @param  CarbonImmutable  $date  Start of the day, in the tournament timezone.
     */
    public function __construct(
        public int $number,
        public CarbonImmutable $date,
    ) {}

    public function label(): string
    {
        return "Day {$this->number}";
    }

    /**
     * First moment of the day, in the timezone timestamps are stored in.
     */
    public function startsAt(): CarbonImmutable
    {
        return $this->date->startOfDay()->setTimezone((string) config('app.timezone'));
    }

    /**
     * First moment of the next day (exclusive end), in the storage timezone.
     */
    public function endsAt(): CarbonImmutable
    {
        return $this->date->addDay()->startOfDay()->setTimezone((string) config('app.timezone'));
    }

    public function isToday(): bool
    {
        return $this->date->toDateString() === now($this->date->getTimezone())->toDateString();
    }

    /**
     * @return array{number: int, label: string, date: string, is_today: bool}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'label' => $this->label(),
            'date' => $this->date->toDateString(),
            'is_today' => $this->isToday(),
        ];
    }
}
