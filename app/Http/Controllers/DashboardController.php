<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "/" : PIC Departemen land on their catalog ([Form 1], the first window of the Laravel 7
 * PIC shell); admin and PIC Gudang on "Dashboard Overview" (rebuilt later).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->role === User::ROLE_PIC_DEPT) {
            return redirect()->route('archives.index');
        }

        return view('placeholder', ['title' => 'Dashboard Overview']);
    }
}
