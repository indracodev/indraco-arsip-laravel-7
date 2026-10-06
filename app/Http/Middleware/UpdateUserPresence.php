<?php

namespace App\Http\Middleware;

use App\Services\UserPresenceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateUserPresence
{
    /**
     * @var UserPresenceService
     */
    protected $presenceService;

    public function __construct(UserPresenceService $presenceService)
    {
        $this->presenceService = $presenceService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only track authenticated users on standard web page navigations (exclude APIs and background pollers)
        if (Auth::check() && $request->isMethod('GET') && !$request->expectsJson() && !$request->is('api/*')) {
            $this->presenceService->recordPresence(Auth::user(), $request);
        }

        return $response;
    }
}
