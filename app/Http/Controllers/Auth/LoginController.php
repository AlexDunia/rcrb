<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'required|string',
                'remember' => 'boolean'
            ]);

            // Find user by email
            $user = User::where('email', $request->email)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid email or password'],
                ]);
            }

            // Create token with abilities based on role
            $token = $user->createToken($request->device_name, [$user->role])->plainTextToken;

            // Generate remember token if requested
            $remember_token = null;
            if ($request->remember) {
                $remember_token = Str::random(60);
                $user->remember_token = $remember_token;
                $user->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
                'remember_token' => $remember_token
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during login.',
            ], 500);
        }
    }

    /**
     * Handle user logout.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Revoke the current token that was used to authenticate
            if ($request->bearerToken()) {
                $user->currentAccessToken()->delete();
            }

            // Clear remember token if it exists
            if ($user->remember_token) {
                $user->remember_token = null;
                $user->save();
            }

            // Clear all tokens if "Logout from all devices" is requested
            if ($request->input('all_devices', false)) {
                $user->tokens()->delete();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            \Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during logout'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Initialize authentication and get CSRF cookie.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function initializeAuth()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'CSRF cookie set'
        ]);
    }

    /**
     * Verify the current token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json([
                'status' => 'success',
                'valid' => true,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token verification failed'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentUser(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated'
                ], Response::HTTP_UNAUTHORIZED);
            }

            return response()->json([
                'status' => 'success',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user data'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
