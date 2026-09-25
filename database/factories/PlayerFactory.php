<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * @var class-string<Player>
     */
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->optional()->name(),
            'blader_name' => fake()->unique()->userName(),
            'date_started' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the player started on a specific date.
     */
    public function startedOn(string $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'date_started' => $date,
        ]);
    }

    /**
     * Put the player on a team (a new one unless given).
     */
    public function onTeam(?Team $team = null): static
    {
        return $this->afterCreating(function (Player $player) use ($team): void {
            $player->teams()->attach($team ?? Team::factory()->create());
        });
    }
}
