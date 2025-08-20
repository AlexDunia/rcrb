<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function initializeAuth()
    {
        if (request()->hasSession()) {
            request()->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'CSRF cookie set successfully',
            'csrf_token' => csrf_token()
        ])->cookie('XSRF-TOKEN', csrf_token(), 60, '/', null, true, false);
    }

    public function register(Request $request)
    {
        Log::info('Registration attempt', ['data' => $request->except('password')]);

        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', Password::min(8)->mixedCase()->numbers()],
                'role' => ['sometimes', 'string', 'in:client,agent'],
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
                'role' => $request->role ?? 'client',
            ]);

            $token = $user->createToken($request->device_name)->plainTextToken;

            Log::info('Registration successful', ['user_id' => $user->id, 'email' => $user->email, 'token' => $token]);

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
            $user->tokens()->where('name', $request->device_name)->delete();
            $token = $user->createToken($request->device_name)->plainTextToken;

            Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email, 'token' => $token]);

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

    public function getCurrentUser(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Log::warning('No authenticated user found', ['token' => $request->bearerToken()]);
                return response()->json([
                    'message' => 'Unauthorized',
                    'error' => 'No authenticated user found'
                ], Response::HTTP_UNAUTHORIZED);
            }
            Log::info('Fetched current user', ['user_id' => $user->id, 'email' => $user->email]);
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Get current user failed', [
                'error' => $e->getMessage(),
                'token' => $request->bearerToken()
            ]);
            return response()->json([
                'message' => 'Failed to get user data',
                'error' => 'An error occurred while fetching user data'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function verifyToken(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid token'
                ], Response::HTTP_UNAUTHORIZED);
            }
            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Token verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token'
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Log::warning('No authenticated user found for logout', ['token' => $request->bearerToken()]);
                return response()->json([
                    'message' => 'No authenticated user found'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Delete token for the device or current token
            if ($request->has('device_name')) {
                $user->tokens()->where('name', $request->device_name)->delete();
            } else {
                $request->user()->currentAccessToken()->delete();
            }

            Log::info('Logout successful', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'message' => 'Logged out successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'token' => $request->bearerToken()
            ]);
            return response()->json([
                'message' => 'Logout failed',
                'error' => 'An error occurred during logout'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
