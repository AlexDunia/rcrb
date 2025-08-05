<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                Log::warning('Unauthorized API access attempt', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->path(),
                    'method' => $request->method()
                ]);

                return response()->json([
                    'message' => 'Unauthorized',
                    'status' => 401
                ], Response::HTTP_UNAUTHORIZED);
            }

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Authentication middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => $request->path()
            ]);

            return response()->json([
                'message' => 'Authentication error',
                'status' => 500
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
