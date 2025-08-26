<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YahooController extends Controller
{
    public function redirect()
    {
        try {
            $url = Socialite::driver('yahoo')
                ->scopes(['sdps-r']) // Read profile data
                ->stateless()
                ->redirect()
                ->getTargetUrl();
            Log::info('Yahoo redirect URL generated', ['url' => $url]);
            return response()->json(['url' => $url], 200);
        } catch (\Exception $e) {
            Log::error('Yahoo redirect failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to generate redirect URL'], 500);
        }
    }

    public function callback()
    {
        try {
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $yahooUser = Socialite::driver('yahoo')
                ->stateless()
                ->setHttpClient($client)
                ->user();

            Log::info('Yahoo user data', [
                'email' => $yahooUser->getEmail(),
                'name' => $yahooUser->getName(),
                'id' => $yahooUser->getId(),
                'attributes' => (array) $yahooUser,
            ]);

            $user = User::firstOrCreate(
                ['email' => $yahooUser->getEmail()],
                [
                    'name' => $yahooUser->getName() ?? $yahooUser->getEmail(),
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'client', // Default role, adjust if needed
                    'yahoo_id' => $yahooUser->getId(),
                ]
            );

            Auth::login($user, true);
            $user->tokens()->where('name', 'web')->delete();
            $token = $user->createToken('web', ['*'])->plainTextToken;

            Log::info('Yahoo login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token' => $token,
            ]);

            return redirect()->away("http://localhost:5173/landing?token={$token}&user=" . urlencode(json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ])));
        } catch (\Exception $e) {
            Log::error('Yahoo login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'yahoo_user' => isset($yahooUser) ? (array) $yahooUser : null,
            ]);
            return redirect()->away('http://localhost:5173/login?error=' . urlencode('Failed to initialize user'));
        }
    }
}
