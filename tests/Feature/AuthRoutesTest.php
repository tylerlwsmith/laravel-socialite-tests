<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OAuth2User;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('authentication routes', function () {
    it('redirects login route to correct Google URL', function () {
        $response = get('/auth/google/redirect');

        $redirect_url = $response->getTargetUrl();
        parse_str(parse_url($redirect_url)['query'] ?? '', $parsed_query);

        $response->assertStatus(302);
        expect($redirect_url)->toStartWith('https://accounts.google.com/o/oauth2/auth');
        expect($parsed_query)->toHaveKeys(['client_id', 'redirect_uri', 'scope', 'response_type', 'state']);
    });

    it('authenticates the user via the callback url and redirects', function () {
        $user = new OAuth2User();
        $user->id = '12345';
        $user->name = 'Tyler Smith';
        $user->email = 'tyler.smith@example.com';
        $user->token = '123456789abcdef';
        $user->refreshToken = '123456789abcdef';

        $mock_provider = Mockery::mock(SocialiteProvider::class);
        $mock_provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($mock_provider);

        $response = get('/auth/google/callback');

        $response->assertRedirect('/');
        assertAuthenticated();
    });

    it('creates the user if the user does not exist', function () {
        $user = new OAuth2User();
        $user->id = '12345';
        $user->name = 'Tyler Smith';
        $user->email = 'tyler.smith@example.com';
        $user->token = '123456789abcdef';
        $user->refreshToken = '123456789abcdef';

        $mock_provider = Mockery::mock(SocialiteProvider::class);
        $mock_provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($mock_provider);

        $response = get('/auth/google/callback');

        expect($response->exceptions)->toHaveCount(0);
        expect(User::count())->toBe(1);
    });

    it('authenticates the user if the user exists', function () {
        $user = new OAuth2User();
        $user->id = '12345';
        $user->name = 'Tyler Smith';
        $user->email = 'tyler.smith@example.com';
        $user->token = '123456789abcdef';
        $user->refreshToken = '123456789abcdef';
        User::factory()->create([
            'email' => $user->email,
        ]);

        $mock_provider = Mockery::mock(SocialiteProvider::class);
        $mock_provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($mock_provider);

        $response = get('/auth/google/callback');

        expect($response->exceptions)->toHaveCount(0);
        expect(User::count())->toBe(1);
    });

    it('returns 400 when the oauth callback url is requested directly', function () {
        $response = get('/auth/google/callback');

        $response->assertStatus(400);
        assertGuest();
    });
});
