<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RaffleEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaffleEntryController extends Controller
{
    /**
     * List raffle entries, optionally filtered by a search term.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $raffleEntries = RaffleEntry::query()
            ->when($search !== '', fn (Builder $query) => $query->whereAny(
                ['name', 'id_number', 'ticket_number'],
                'like',
                "%{$search}%",
            ))
            ->latest()
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.raffle-entries.index', [
            'raffleEntries' => $raffleEntries,
            'search' => $search,
            'totalEntries' => RaffleEntry::count(),
        ]);
    }
}
