<?php

namespace App\Observers;

use App\Models\ActionLog;
use App\Models\PlayerScore;
use Illuminate\Support\Facades\Auth;

class PlayerScoreObserver
{
    /**
     * Handle the PlayerScore "created" event.
     */
    public function created(PlayerScore $playerScore): void
    {
        $this->log('created', $playerScore, null, $this->snapshot($playerScore));
    }

    /**
     * Handle the PlayerScore "updated" event.
     */
    public function updated(PlayerScore $playerScore): void
    {
        $old = [
            'score' => $playerScore->getOriginal('score'),
            'is_burst' => (bool) $playerScore->getOriginal('is_burst'),
        ];

        $this->log('updated', $playerScore, $old, $this->snapshot($playerScore));
    }

    /**
     * Handle the PlayerScore "deleted" event.
     */
    public function deleted(PlayerScore $playerScore): void
    {
        $this->log('deleted', $playerScore, $this->snapshot($playerScore), null);
    }

    /**
     * Build the current attribute snapshot for a score.
     *
     * @return array{score: int, is_burst: bool}
     */
    protected function snapshot(PlayerScore $playerScore): array
    {
        return [
            'score' => (int) $playerScore->score,
            'is_burst' => (bool) $playerScore->is_burst,
        ];
    }

    /**
     * Persist an audit log entry describing the score change.
     *
     * @param  array{score: int, is_burst: bool}|null  $old
     * @param  array{score: int, is_burst: bool}|null  $new
     */
    protected function log(string $action, PlayerScore $playerScore, ?array $old, ?array $new): void
    {
        ActionLog::create([
            'user_id' => Auth::id(),
            'changes' => [
                'action' => $action,
                'subject' => 'score',
                'score_id' => $playerScore->id,
                'player_id' => $playerScore->player_id,
                'player_name' => $playerScore->player?->name,
                'old' => $old,
                'new' => $new,
            ],
        ]);
    }
}
