<?php

namespace App\Models;

use Database\Factories\PrizeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * A raffle prize. Prizes are drawn in ascending position order, so the
 * highest position is the grand prize.
 */
#[Fillable(['name', 'position'])]
class Prize extends Model
{
    /** @use HasFactory<PrizeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Every draw for this prize, including forfeited ones.
     *
     * @return HasMany<PrizeDraw, $this>
     */
    public function draws(): HasMany
    {
        return $this->hasMany(PrizeDraw::class);
    }

    /**
     * The draw that currently holds this prize.
     *
     * @return HasOne<PrizeDraw, $this>
     */
    public function winningDraw(): HasOne
    {
        return $this->hasOne(PrizeDraw::class)->whereNull('forfeited_at');
    }

    /**
     * Order prizes from the lowest to the highest (the grand prize).
     *
     * @param  Builder<Prize>  $query
     */
    #[Scope]
    protected function lowestFirst(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * The position a newly added prize takes: after every existing prize.
     */
    public static function nextPosition(): int
    {
        return (int) static::max('position') + 1;
    }

    /**
     * Draw a random eligible ticket for this prize. Any current winner forfeits the prize.
     *
     * Returns null when no ticket is eligible to win.
     */
    public function drawWinner(): ?PrizeDraw
    {
        $eligibleEntryIds = RaffleEntry::query()->eligibleToWin()->pluck('id');

        if ($eligibleEntryIds->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($eligibleEntryIds): PrizeDraw {
            $this->draws()->whereNull('forfeited_at')->update(['forfeited_at' => now()]);

            return $this->draws()->create([
                'raffle_entry_id' => $eligibleEntryIds[random_int(0, $eligibleEntryIds->count() - 1)],
            ]);
        });
    }
}
