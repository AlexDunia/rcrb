<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirect()
    {
        try {
            $url = Socialite::driver('google')
                ->scopes(['openid', 'email', 'profile'])
                ->stateless()
                ->redirect()
                ->getTargetUrl();
            Log::info('Google redirect URL generated', ['url' => $url]);
            return response()->json(['url' => $url], 200);
        } catch (\Exception $e) {
            Log::error('Google redirect failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate redirect URL'], 500);
        }
    }

    public function callback()
    {
        try {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->setHttpClient($client)
                ->user();

            Log::info('Google user data', [
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'id' => $googleUser->getId(),
                'attributes' => (array) $googleUser,
            ]);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?? $googleUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'client',
                    'google_id' => $googleUser->getId(),
                ]
            );

            Auth::login($user, true);
            $user->tokens()->where('name', 'web')->delete();
            $token = $user->createToken('web')->plainTextToken;

            Log::info('Google login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => $token,
            ]);

            return redirect()->away("http://localhost:5173/landing?token={$token}");
        } catch (\Exception $e) {
            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'google_user' => isset($googleUser) ? (array) $googleUser : null,
            ]);
            return redirect()->away('http://localhost:5173/login?error=' . urlencode('Google login failed, please try again.'));
        }
    }
}
