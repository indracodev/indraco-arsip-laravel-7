<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Menu entries whose module is not rebuilt yet: same URL, route name and role check as
 * Laravel 7, page says "Sedang dibangun ulang". Each module replaces its route.
 */
class PlaceholderController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $route = $request->route();
        $title = Menu::labelFor($user, $route?->getName()) ?? (string) $route?->defaults['title'];

        return view('placeholder', ['title' => $title]);
    }
}
