<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    // Redirect to Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Handle callback from Google
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(str()->random(16)), // random password since Google handles login
                ]
            );

            Auth::login($user);

            // For API / SPA: return token instead of redirecting
            $token = $user->createToken('api_token')->plainTextToken;

            return redirect("http://localhost:5173/landing?token={$token}");
        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['msg' => 'Google login failed']);
        }
    }
}
