<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Get Google OAuth URL
     */
    public function getGoogleAuthUrl()
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'status' => 'success',
                'redirect_url' => $url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate Google login URL'
            ], 500);
        }
    }

    /**
     * Handle Google callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Find or create user
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'client', // Default role for social login
                ]);
            }

            // Create token
            $token = $user->createToken('google-auth', [$user->role])->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = config('app.frontend_url');
            return redirect()->away("{$frontendUrl}/social-auth-callback?token={$token}&user=" . json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]));

        } catch (\Exception $e) {
            $frontendUrl = config('app.frontend_url');
            return redirect()->away("{$frontendUrl}/social-auth-callback?error=Authentication failed");
        }
    }
}
