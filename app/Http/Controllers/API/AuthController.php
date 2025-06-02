<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Initialize authentication session
     */
    public function initializeAuth()
    {
        if (request()->hasSession()) {
            request()->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'CSRF cookie set successfully',
            'csrf_token' => csrf_token()
        ])
        ->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => 'http://localhost:5173',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, X-XSRF-TOKEN',
        ])
        ->cookie('XSRF-TOKEN', csrf_token(), 60, '/', null, true, false);
    }

    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        Log::info('Registration attempt', ['data' => $request->except('password')]);

        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', Password::min(8)->mixedCase()->numbers()],
                'role' => ['required', 'string', 'in:client,agent'],
                'device_name' => ['required', 'string']
            ]);

            if ($validator->fails()) {
                Log::warning('Registration validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            // Create token with device name
            $token = $user->createToken($request->device_name)->plainTextToken;

            Log::info('Registration successful', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Registration successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => 'An error occurred during registration'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
                'device_name' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            // Revoke all existing tokens for this device
            $user->tokens()->where('name', $request->device_name)->delete();

            // Create new token
            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Login failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Login failed',
                'error' => 'An error occurred during login'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current user
     */
    public function getCurrentUser(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get current user failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to get user data',
                'error' => 'An error occurred while fetching user data'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify token
     */
    public function verifyToken(Request $request)
    {
        try {
            $user = $request->user();
            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            // Get device name from token
            $token = PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                // Only revoke tokens for this device
                $request->user()->tokens()->where('name', $token->name)->delete();
            }

            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Logout failed',
                'error' => 'An error occurred during logout'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
