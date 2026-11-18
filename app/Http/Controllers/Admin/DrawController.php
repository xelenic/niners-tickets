<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prize;
use App\Models\PrizeDraw;
use App\Models\RaffleEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DrawController extends Controller
{
    /**
     * Show the draw control panel.
     */
    public function index(): View
    {
        $prizes = Prize::query()->lowestFirst()->with('winningDraw.raffleEntry')->get();

        return view('admin.draw.index', [
            'prizes' => $prizes,
            'nextPrize' => $prizes->first(fn (Prize $prize): bool => $prize->winningDraw === null),
            'eligibleEntries' => RaffleEntry::query()->eligibleToWin()->count(),
            'totalEntries' => RaffleEntry::count(),
        ]);
    }

    /**
     * Clear every draw, e.g. after a rehearsal.
     */
    public function destroy(): RedirectResponse
    {
        PrizeDraw::query()->delete();

        return to_route('admin.draw.index')->with('status', 'All draws have been reset.');
    }
}
