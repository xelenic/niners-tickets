<?php

namespace App\Models;

use Database\Factories\PrizeDrawFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One drawn ticket for a prize. A redraw marks the previous draw as forfeited
 * (e.g. the winner was not present) and records a new one.
 */
#[Fillable(['raffle_entry_id'])]
class PrizeDraw extends Model
{
    /** @use HasFactory<PrizeDrawFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'forfeited_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Prize, $this>
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    /**
     * @return BelongsTo<RaffleEntry, $this>
     */
    public function raffleEntry(): BelongsTo
    {
        return $this->belongsTo(RaffleEntry::class);
    }
}
