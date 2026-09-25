<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $acronym
 * @property string|null $logo_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'acronym', 'logo_path'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * The players on the team.
     *
     * @return BelongsToMany<Player, $this, TeamMember>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'team_members')
            ->using(TeamMember::class)
            ->withTimestamps();
    }

    /**
     * The public URL of the team's logo, if it has one.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset($this->logo_path) : null;
    }

    /**
     * The team as shown next to a blader name.
     *
     * @return array{name: string, acronym: string|null, logo_url: string|null}
     */
    public function summary(): array
    {
        return [
            'name' => $this->name,
            'acronym' => $this->acronym,
            'logo_url' => $this->logoUrl(),
        ];
    }
}
