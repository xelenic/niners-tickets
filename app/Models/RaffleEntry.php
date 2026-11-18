<?php

namespace App\Models;

use Database\Factories\RaffleEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'id_number', 'ticket_number'])]
class RaffleEntry extends Model
{
    /** @use HasFactory<RaffleEntryFactory> */
    use HasFactory;

    /**
     * @return HasMany<PrizeDraw, $this>
     */
    public function draws(): HasMany
    {
        return $this->hasMany(PrizeDraw::class);
    }

    /**
     * Tickets that can still win: one prize per person, so every ticket of
     * anyone already drawn (including forfeited draws) is left out.
     *
     * @param  Builder<RaffleEntry>  $query
     */
    #[Scope]
    protected function eligibleToWin(Builder $query): void
    {
        $query->whereNotIn(
            'id_number',
            RaffleEntry::query()->select('id_number')->whereHas('draws'),
        );
    }
}
