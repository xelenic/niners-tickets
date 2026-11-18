<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prize;
use App\Models\RaffleEntry;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function __invoke(): View
    {
        // "Today" follows the raffle's local day, converted to the timezone timestamps are stored in.
        $startOfToday = now(config('raffle.timezone'))
            ->startOfDay()
            ->setTimezone(config('app.timezone'));

        return view('admin.dashboard', [
            'totalEntries' => RaffleEntry::count(),
            'entriesToday' => RaffleEntry::where('created_at', '>=', $startOfToday)->count(),
            'uniqueEntrants' => RaffleEntry::distinct()->count('id_number'),
            'prizeCount' => Prize::count(),
            'latestEntries' => RaffleEntry::latest()->latest('id')->limit(8)->get(),
        ]);
    }
}
