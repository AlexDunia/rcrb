<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'client',
        ]);

        $token = $user->createToken($data['device_name'])->plainTextToken;

        Log::info('User registered successfully', ['user_id' => $user->id]);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ];
    }

    /**
     * Login user
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke existing tokens for this device
        $user->tokens()->where('name', $data['device_name'])->delete();

        // Create new token
        $token = $user->createToken($data['device_name'])->plainTextToken;

        Log::info('User logged in successfully', ['user_id' => $user->id]);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ];
    }

    /**
     * Logout user
     */
    public function logout(User $user, string $deviceName): bool
    {
        try {
            $deletedTokens = $user->tokens()
                ->where('name', $deviceName)
                ->delete();

            Log::info('User logout successful', [
                'user_id' => $user->id,
                'device_name' => $deviceName,
                'tokens_deleted' => $deletedTokens
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id,
                'device_name' => $deviceName,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get current user data
     */
    public function getCurrentUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }

    /**
     * Verify token and return user data
     */
    public function verifyToken(User $user): array
    {
        return [
            'valid' => true,
            'user' => $this->getCurrentUser($user)
        ];
    }
}
