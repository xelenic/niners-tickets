<?php

namespace App\Http\Controllers;

use App\Models\Prize;
use App\Models\PrizeDraw;
use App\Models\RaffleEntry;
use Illuminate\Http\JsonResponse;

class ScreenStateController extends Controller
{
    /**
     * The current draw state polled by the LED screen.
     *
     * Only what the audience sees is included: winners' names and ticket
     * numbers, never their ID numbers.
     */
    public function __invoke(): JsonResponse
    {
        $prizes = Prize::query()->lowestFirst()->with('winningDraw.raffleEntry')->get();
        $latestDraw = PrizeDraw::query()->whereNull('forfeited_at')->latest('id')->first();

        return response()->json([
            'total_entries' => RaffleEntry::count(),
            'prizes' => $prizes->values()->map(fn (Prize $prize, int $index): array => [
                'id' => $prize->id,
                'rank' => $index + 1,
                'name' => $prize->name,
                'is_grand_prize' => $index === $prizes->count() - 1,
                'winner' => $prize->winningDraw ? [
                    'name' => $prize->winningDraw->raffleEntry->name,
                    'ticket_number' => $prize->winningDraw->raffleEntry->ticket_number,
                ] : null,
            ]),
            'latest_draw' => $latestDraw ? [
                'id' => $latestDraw->id,
                'prize_id' => $latestDraw->prize_id,
            ] : null,
            // Random entries for the rolling animation before a winner is revealed.
            'roll_entries' => RaffleEntry::query()
                ->inRandomOrder()
                ->limit(60)
                ->get(['name', 'ticket_number'])
                ->map(fn (RaffleEntry $entry): array => [
                    'ticket_number' => $entry->ticket_number,
                    'name' => $entry->name,
                ]),
        ])->header('Cache-Control', 'no-store');
    }
}
