<?php

namespace App\Http\Controllers;

use App\Models\Prize;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ScreenDrawController extends Controller
{
    /**
     * Draw the next prize from the LED screen (the operator presses Enter).
     *
     * On success this redirects to the screen state, so the screen gets the
     * new winner in the same request and can start the animation right away.
     */
    public function __invoke(): RedirectResponse|JsonResponse
    {
        $nextPrize = Prize::query()->lowestFirst()->whereDoesntHave('winningDraw')->first();

        if (! $nextPrize) {
            return response()->json(['message' => 'All prizes have been drawn.'], 422);
        }

        if (! $nextPrize->drawWinner()) {
            return response()->json(['message' => 'There are no tickets left that can win.'], 422);
        }

        return to_route('admin.screen.state', status: 303);
    }
}
