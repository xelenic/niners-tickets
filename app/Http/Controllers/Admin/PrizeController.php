<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PrizeRequest;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrizeController extends Controller
{
    /**
     * List the prizes from the lowest to the grand prize.
     */
    public function index(): View
    {
        return view('admin.prizes.index', [
            'prizes' => Prize::query()->lowestFirst()->get(),
        ]);
    }

    /**
     * Add a prize after the existing ones.
     */
    public function store(PrizeRequest $request): RedirectResponse
    {
        Prize::create([
            ...$request->validated(),
            'position' => Prize::nextPosition(),
        ]);

        return to_route('admin.prizes.index')->with('status', 'Prize added.');
    }

    /**
     * Show the form for renaming a prize.
     */
    public function edit(Prize $prize): View
    {
        return view('admin.prizes.edit', [
            'prize' => $prize,
        ]);
    }

    /**
     * Rename a prize.
     */
    public function update(PrizeRequest $request, Prize $prize): RedirectResponse
    {
        $prize->update($request->validated());

        return to_route('admin.prizes.index')->with('status', 'Prize updated.');
    }

    /**
     * Remove a prize.
     */
    public function destroy(Prize $prize): RedirectResponse
    {
        $prize->delete();

        return to_route('admin.prizes.index')->with('status', 'Prize deleted.');
    }
}
