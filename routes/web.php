<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as OAuth2User;

Route::get('/', function () {
    return view('homepage');
});

Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/auth/google/callback', function () {
    try {
        /** @var OAuth2User $googleUser */
        $googleUser = Socialite::driver('google')->user();
    } catch (InvalidStateException $exception) {
        abort(400, $exception->getMessage());
    }

    $user = User::updateOrCreate([
        'email' => $googleUser->email,
    ], [
        'google_id' => $googleUser->id,
        'name' => $googleUser->name,
        'google_token' => $googleUser->token,
        'google_refresh_token' => $googleUser->refreshToken,
    ]);

    Auth::login($user);

    return redirect('/');
});

Route::get('/auth/logout', function () {
    Auth::logout();

    return redirect('/');
});
