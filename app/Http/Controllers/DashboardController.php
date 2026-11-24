<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Temporary landing page after login (Phase 2). The real dashboard/shell is built in Phase 3+.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
