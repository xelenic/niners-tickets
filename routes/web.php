<?php

use App\Http\Controllers\Admin\AuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DrawController;
use App\Http\Controllers\Admin\PrizeController;
use App\Http\Controllers\Admin\PrizeDrawController;
use App\Http\Controllers\Admin\PrizePositionController;
use App\Http\Controllers\Admin\RaffleEntryController as AdminRaffleEntryController;
use App\Http\Controllers\RaffleEntryController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\ScreenDrawController;
use App\Http\Controllers\ScreenStateController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RaffleEntryController::class, 'create'])->name('raffle-entries.create');
Route::post('/raffle-entries', [RaffleEntryController::class, 'store'])->name('raffle-entries.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')
            ->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::middleware('can:access-admin')->group(function () {
            Route::get('/', DashboardController::class)->name('dashboard');
            Route::get('raffle-entries', [AdminRaffleEntryController::class, 'index'])->name('raffle-entries.index');

            Route::resource('prizes', PrizeController::class)->except(['create', 'show']);
            Route::patch('prizes/{prize}/position', [PrizePositionController::class, 'update'])->name('prizes.position.update');

            Route::get('draw', [DrawController::class, 'index'])->name('draw.index');
            Route::delete('draw', [DrawController::class, 'destroy'])->name('draw.destroy');
            Route::post('prizes/{prize}/draws', [PrizeDrawController::class, 'store'])->name('prizes.draws.store');

            // The LED winners screen draws when the operator presses Enter, so it is admin-only.
            Route::get('screen', ScreenController::class)->name('screen');
            Route::get('screen/state', ScreenStateController::class)->name('screen.state');
            Route::post('screen/draw', ScreenDrawController::class)->name('screen.draw');
        });
    });
});
