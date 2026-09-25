<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Bladers';

        return [
            'name' => $name,
            'acronym' => strtoupper(fake()->unique()->lexify('???')),
            'logo_path' => 'images/teams/'.str($name)->slug().'.webp',
        ];
    }

    /**
     * Indicate that the team has no logo.
     */
    public function withoutLogo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'logo_path' => null,
        ]);
    }
}
