<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ScreenController extends Controller
{
    /**
     * Show the winners screen for the LED wall.
     */
    public function __invoke(): View
    {
        return view('screen');
    }
}
