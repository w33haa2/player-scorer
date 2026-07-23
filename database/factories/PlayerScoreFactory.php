<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PlayerScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerScore>
 */
class PlayerScoreFactory extends Factory
{
    /**
     * @var class-string<PlayerScore>
     */
    protected $model = PlayerScore::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'score' => fake()->numberBetween(1, 3),
            'is_burst' => false,
        ];
    }

    /**
     * A burst finish always scores exactly 2.
     */
    public function burst(): static
    {
        return $this->state(fn (array $attributes): array => [
            'score' => 2,
            'is_burst' => true,
        ]);
    }

    /**
     * Force a specific score value.
     */
    public function score(int $score): static
    {
        return $this->state(fn (array $attributes): array => [
            'score' => $score,
        ]);
    }
}
