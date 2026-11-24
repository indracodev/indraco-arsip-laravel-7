<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\UserPresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records "online" presence for page navigations (GET, not API/JSON), at most once per
 * THROTTLE_SECONDS per session. Laravel 7 wrote three cache files every 15 s per user;
 * the throttle timestamp now lives in the session, which is saved on every request anyway.
 */
class UpdateUserPresence
{
    public const THROTTLE_SECONDS = 60;

    public const SESSION_KEY = 'presence_recorded_at';

    public function __construct(private readonly UserPresenceService $presence) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (! $user instanceof User || ! $request->isMethod('GET') || $request->expectsJson() || $request->is('api/*')) {
            return $response;
        }

        $now = now()->getTimestamp();
        if ($now - (int) $request->session()->get(self::SESSION_KEY, 0) < self::THROTTLE_SECONDS) {
            return $response;
        }

        $request->session()->put(self::SESSION_KEY, $now);
        $this->presence->recordPresence($user, $request);

        return $response;
    }
}
