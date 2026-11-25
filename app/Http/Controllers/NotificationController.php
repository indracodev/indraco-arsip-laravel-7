<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Polled by public/js/pages/notifications.js (every page, 15 s ± 2 s with a random first delay, paused while the tab is hidden):
 * toasts + bell list, menu badges and the expiry popup. Laravel 7 URLs:
 * /api/realtime/check (?since_seq=, ?initial=1) and /api/archives/expiring-list.
 */
class NotificationController extends Controller
{
    public function poll(Request $request, NotificationFeed $feed): JsonResponse
    {
        return response()->json($feed->poll($this->user($request), max(0, (int) $request->query('since_seq')), $request->boolean('initial')))
            ->header('Cache-Control', 'no-store');
    }

    public function expiring(Request $request, NotificationFeed $feed): JsonResponse
    {
        $items = $feed->expiring($this->user($request));

        return response()->json(['count' => count($items), 'items' => $items])->header('Cache-Control', 'no-store');
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
