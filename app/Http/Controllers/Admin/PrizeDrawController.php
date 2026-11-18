<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PrizeDrawController extends Controller
{
    /**
     * Draw a winner for a prize, or redraw it when asked to explicitly.
     *
     * Redraw must be requested explicitly so a repeated "Draw" click can never
     * replace a winner who was just drawn.
     */
    public function store(Request $request, Prize $prize): RedirectResponse
    {
        $hasWinner = $prize->winningDraw()->exists();
        $isRedraw = $request->boolean('redraw');

        if ($hasWinner && ! $isRedraw) {
            return back()->withErrors(['draw' => "“{$prize->name}” already has a winner."]);
        }

        if (! $hasWinner && $isRedraw) {
            return back()->withErrors(['draw' => "“{$prize->name}” has no winner to redraw."]);
        }

        if (! $hasWinner) {
            $nextPrize = Prize::query()->lowestFirst()->whereDoesntHave('winningDraw')->first();

            if (! $prize->is($nextPrize)) {
                return back()->withErrors([
                    'draw' => "Prizes are drawn from the lowest up. Draw “{$nextPrize->name}” next.",
                ]);
            }
        }

        $prizeDraw = $prize->drawWinner();

        if (! $prizeDraw) {
            return back()->withErrors([
                'draw' => 'There are no tickets left that can win. Each person can only win one prize.',
            ]);
        }

        $winner = $prizeDraw->raffleEntry;

        return to_route('admin.draw.index')->with(
            'status',
            "{$winner->name} (ticket {$winner->ticket_number}) won “{$prize->name}”. The LED screen is revealing it now.",
        );
    }
}
