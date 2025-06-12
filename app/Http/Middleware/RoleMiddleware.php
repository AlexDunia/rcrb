<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. User not authenticated.',
            ], 401);
        }

        $userRole = $request->user()->role;

        // Admin has access to everything
        if ($userRole === 'admin') {
            return $next($request);
        }

        // Check if user has one of the required roles
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Insufficient role permissions.',
                'required_role' => implode('/', $roles)
            ], 403);
        }

        // For Sanctum tokens, verify abilities
        if ($request->user()->currentAccessToken()) {
            $token = $request->user()->currentAccessToken();

            // Check if token has the required role ability
            if (!$token->can($userRole)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Token does not have required abilities.',
                    'required_role' => implode('/', $roles)
                ], 403);
            }
        }

        return $next($request);
    }
}
