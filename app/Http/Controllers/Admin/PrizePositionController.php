<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrizePositionController extends Controller
{
    /**
     * Swap a prize with its neighbour in the list.
     *
     * "up" moves it towards the lowest prize, "down" towards the grand prize,
     * matching how the list is shown.
     */
    public function update(Request $request, Prize $prize): RedirectResponse
    {
        $direction = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ])['direction'];

        $neighbour = Prize::query()
            ->when(
                $direction === 'up',
                fn ($query) => $query->where('position', '<', $prize->position)->orderByDesc('position'),
                fn ($query) => $query->where('position', '>', $prize->position)->orderBy('position'),
            )
            ->first();

        if ($neighbour) {
            DB::transaction(function () use ($prize, $neighbour): void {
                [$prize->position, $neighbour->position] = [$neighbour->position, $prize->position];

                $prize->save();
                $neighbour->save();
            });
        }

        return to_route('admin.prizes.index');
    }
}
