<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $name Real name (optional).
 * @property string $blader_name The name shown on standings and scores.
 * @property Carbon|null $date_started
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'blader_name', 'date_started'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * The scores recorded for the player.
     *
     * @return HasMany<PlayerScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(PlayerScore::class);
    }

    /**
     * The teams the player belongs to, oldest membership first.
     *
     * @return BelongsToMany<Team, $this, TeamMember>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->using(TeamMember::class)
            ->withTimestamps()
            ->orderByPivot('id');
    }

    /**
     * The team the player currently represents (their latest membership).
     *
     * Expects `teams` to be eager loaded when used in a list.
     */
    public function currentTeam(): ?Team
    {
        return $this->teams->last();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_started' => 'date',
        ];
    }
}
