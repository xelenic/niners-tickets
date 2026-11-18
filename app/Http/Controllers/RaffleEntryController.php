<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRaffleEntryRequest;
use App\Models\RaffleEntry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RaffleEntryController extends Controller
{
    /**
     * Show the kiosk entry form.
     */
    public function create(): View
    {
        return view('raffle-entries.create');
    }

    /**
     * Store a raffle entry submitted from the kiosk.
     */
    public function store(StoreRaffleEntryRequest $request): RedirectResponse
    {
        try {
            $raffleEntry = RaffleEntry::create($request->validated());
        } catch (UniqueConstraintViolationException) {
            // Another kiosk registered the same ticket between validation and insert.
            return back()
                ->withInput()
                ->withErrors(['ticket_number' => StoreRaffleEntryRequest::DUPLICATE_TICKET_MESSAGE]);
        }

        return to_route('raffle-entries.create')->with('registered_entry', [
            'name' => $raffleEntry->name,
            'ticket_number' => $raffleEntry->ticket_number,
        ]);
    }
}
