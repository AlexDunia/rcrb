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
            $redirectUri = env('YAHOO_REDIRECT_URI', 'https://127.0.0.1:8000/api/auth/yahoo/callback');
            $config = config('services.yahoo');
            Log::info('Yahoo redirect config', [
                'client_id' => $config['client_id'] ?? 'not set',
                'client_secret' => $config['client_secret'] ? 'set' : 'not set',
                'redirect' => $config['redirect'] ?? 'not set',
                'scopes' => $config['scopes'] ?? 'not set',
                'env_redirect_uri' => env('YAHOO_REDIRECT_URI', 'not set'),
            ]);

            if (!$redirectUri) {
                Log::error('Yahoo redirect URI not set in configuration');
                return response()->json(['error' => 'Redirect URI not configured'], 500);
            }

            $url = Socialite::driver('yahoo')
                ->stateless()
                ->redirectUrl($redirectUri)
                ->redirect()
                ->getTargetUrl();

            Log::info('Yahoo redirect URL generated', ['url' => $url]);
            return response()->json(['url' => $url], 200);
        } catch (\Exception $e) {
            Log::error('Yahoo redirect failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to generate redirect URL'], 500);
        }
    }

    public function callback()
    {
        try {
            $redirectUri = env('YAHOO_REDIRECT_URI', 'https://127.0.0.1:8000/api/auth/yahoo/callback');
            Log::info('Yahoo callback initiated', [
                'redirect_uri' => $redirectUri,
                'query_params' => request()->all(),
                'code' => request()->query('code', 'not provided'),
                'state' => request()->query('state', 'not provided'),
            ]);

            if (!request()->has('code')) {
                Log::error('Yahoo callback missing code parameter');
                throw new \Exception('Authorization code missing in callback');
            }

            $client = new \GuzzleHttp\Client(['verify' => false]);
            $yahooUser = Socialite::driver('yahoo')
                ->stateless()
                ->redirectUrl($redirectUri)
                ->setHttpClient($client)
                ->user();

            Log::info('Yahoo user data retrieved', [
                'email' => $yahooUser->getEmail(),
                'name' => $yahooUser->getName(),
                'id' => $yahooUser->getId(),
                'attributes' => (array) $yahooUser,
            ]);

            if (!$yahooUser->getEmail()) {
                Log::error('Yahoo user email is null');
                throw new \Exception('Yahoo user email is missing');
            }

            $user = User::firstOrCreate(
                ['yahoo_id' => $yahooUser->getId()],
                [
                    'email' => $yahooUser->getEmail() ?? 'yahoo_' . $yahooUser->getId() . '@placeholder.com',
                    'name' => $yahooUser->getName() ?? 'Yahoo User',
                    'password' => bcrypt(Str::random(16)),
                    'role' => 'client',
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
                'request_data' => request()->all(),
            ]);
            return redirect()->away('http://localhost:5173/login?error=' . urlencode($e->getMessage()));
        }
    }
}
