<?php
// app/Http/Controllers/Auth/GoogleController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            Log::info('Google user data', [
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'id' => $googleUser->getId(),
                'attributes' => (array) $googleUser,
            ]);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?? 'Unknown',
                    'password' => bcrypt(str()->random(16)),
                    'role' => 'client',
                ]
            );

            Auth::login($user);

            $token = $user->createToken('api_token')->plainTextToken;

            Log::info('Google login successful', [
                'user_id' => $user->id,
                'token' => $token,
            ]);

            return redirect()->away("http://localhost:5173/landing?token={$token}");
        } catch (\Exception $e) {
            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'google_user' => isset($googleUser) ? (array) $googleUser : null,
            ]);
            return redirect()->away('http://localhost:5173/login?error=' . urlencode('Google login failed: ' . $e->getMessage()));
        }
    }
}
