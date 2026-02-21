<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;

class RateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle($request, Closure $next)
    {
        $limiterKey = Auth::user()->getAuthIdentifier() ?? $request->ip();

        if (!RateLimiterFacade::attempt($limiterKey, config('rate_limit.rename_host_throttle_count'), fn() => null, 60)) {
            $seconds = RateLimiterFacade::availableIn($limiterKey);
            $humanTimeDiff = now()->addSeconds($seconds)->diffForHumans([
                'parts' => 1,
            ]);

            throw new ThrottleRequestsException("Превышен лимит запросов, можно повторить $humanTimeDiff");
        }
        return $next($request);
    }
}
