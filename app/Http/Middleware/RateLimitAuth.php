<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RateLimitAuth
{
    /**
     * The maximum number of attempts allowed.
     */
    protected $maxAttempts = 5;

    /**
     * The number of minutes to lock the feature.
     */
    protected $decayMinutes = 5;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after' => $seconds,
                'minutes_until_available' => ceil($seconds / 60),
            ], Response::HTTP_TOO_MANY_REQUESTS)->withHeaders([
                'Retry-After' => $seconds,
                'X-RateLimit-Reset' => now()->addSeconds($seconds)->getTimestamp(),
            ]);
        }

        RateLimiter::hit($key, $this->decayMinutes * 60);

        $response = $next($request);

        // If the request was successful (2xx status code), clear the rate limit
        if ($response instanceof SymfonyResponse && $response->isSuccessful()) {
            RateLimiter::clear($key);
        }

        return $response->withHeaders([
            'X-RateLimit-Limit' => $this->maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $this->maxAttempts),
        ]);
    }

    /**
     * Resolve request signature.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(implode('|', [
            $request->ip(),
            $request->header('X-Forwarded-For', ''),
            $request->header('User-Agent', ''),
            'auth_attempts'
        ]));
    }
}
