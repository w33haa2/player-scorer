<?php

namespace App\Models;

use App\Observers\PlayerScoreObserver;
use Database\Factories\PlayerScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $player_id
 * @property int $score
 * @property bool $is_burst
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Player|null $player
 */
#[Fillable(['player_id', 'score', 'is_burst'])]
#[ObservedBy(PlayerScoreObserver::class)]
class PlayerScore extends Model
{
    /** @use HasFactory<PlayerScoreFactory> */
    use HasFactory;

    /**
     * The player the score belongs to.
     *
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'is_burst' => 'boolean',
        ];
    }
}
