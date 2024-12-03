<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MastodonController extends Controller
{
    public function redirect()
    {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => config('services.mastodon.client_id'),
            'clientSecret'            => config('services.mastodon.client_secret'),
            'redirectUri'             => config('services.mastodon.redirect_uri'),
            'urlAuthorize'            => config('services.mastodon.base_url') . '/oauth/authorize',
            'urlAccessToken'          => config('services.mastodon.base_url') . '/oauth/token',
            'urlResourceOwnerDetails' => config('services.mastodon.base_url') . '/api/v1/accounts/verify_credentials',
        ]);

        // Generar URL de autorización
        $authorizationUrl = $provider->getAuthorizationUrl(['scope' => 'read write follow']);
        session(['oauth2state' => $provider->getState()]);

        return redirect($authorizationUrl);
    }

    public function callback(Request $request)
    {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => config('services.mastodon.client_id'),
            'clientSecret'            => config('services.mastodon.client_secret'),
            'redirectUri'             => config('services.mastodon.redirect_uri'),
            'urlAuthorize'            => config('services.mastodon.base_url') . '/oauth/authorize',
            'urlAccessToken'          => config('services.mastodon.base_url') . '/oauth/token',
            'urlResourceOwnerDetails' => config('services.mastodon.base_url') . '/api/v1/accounts/verify_credentials',
        ]);

        if ($request->get('state') !== session('oauth2state')) {
            session()->forget('oauth2state');
            return redirect()->route('dashboard')->with('error', 'Invalid OAuth state');
        }

        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            Auth::user()->update([
                'mastodon_token' => $accessToken->getToken(),
                'mastodon_token_expires_at' => now()->addSeconds($accessToken->getExpires()),
            ]);

            return redirect()->route('dashboard')->with('success', 'Mastodon account linked successfully!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to link Mastodon account: ' . $e->getMessage());
        }

        Log::error('OAuth Error', ['response' => $e->getMessage()]);

    }

    public function createPost(Request $request)
    {
        if (!Auth::user()->mastodon_token) {
            return redirect()->route('dashboard')->with('error', 'You need to link your Mastodon account first.');
        }
    
        // Validar si el token ha expirado
        if (Auth::user()->mastodon_token_expires_at && Auth::user()->mastodon_token_expires_at->isPast()) {
            return redirect()->route('dashboard')->with('error', 'Your Mastodon token has expired. Please re-link your account.');
        }
    
        $status = $request->input('status', 'Hello Mastodon! This is a test post from Laravel 🚀');
    
        \Log::info('Attempting to post on Mastodon', [
            'status' => $status,
            'base_url' => config('services.mastodon.base_url'),
        ]);
    
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . Auth::user()->mastodon_token,
            ])->post(config('services.mastodon.base_url') . '/api/v1/statuses', [
                'status' => $status,
            ]);
    
            // Log para revisar respuesta completa
            \Log::info('Mastodon API Response', [
                'status_code' => $response->status(),
                'response_body' => $response->json(),
            ]);
    
            if ($response->successful()) {
                \Log::info('Mastodon post successful', ['response' => $response->json()]);
                return redirect()->route('dashboard')->with('success', 'Post created successfully on Mastodon!');
            }
    
            \Log::error('Failed to post on Mastodon', [
                'response' => $response->body(),
                'status_code' => $response->status(),
            ]);
    
            return redirect()->route('dashboard')->with('error', 'Failed to create post: ' . ($response->json('error') ?? 'Unknown error.'));
        } catch (\Exception $e) {
            \Log::error('Exception while posting on Mastodon', ['exception' => $e->getMessage()]);
    
            return redirect()->route('dashboard')->with('error', 'An error occurred while posting on Mastodon: ' . $e->getMessage());
        }
    }
    
    
}


