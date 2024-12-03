<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Client\Provider\GenericProvider;

class RedditController extends Controller
{
    /**
     * Redirige al usuario a la página de autorización de Reddit.
     */

    public function redirect()
    {
        $provider = new GenericProvider([
            'clientId'                => config('services.reddit.client_id'),
            'clientSecret'            => config('services.reddit.client_secret'),
            'redirectUri'             => config('services.reddit.redirect'),
            'urlAuthorize'            => 'https://www.reddit.com/api/v1/authorize',
            'urlAccessToken'          => 'https://www.reddit.com/api/v1/access_token',
            'urlResourceOwnerDetails' => 'https://oauth.reddit.com/api/v1/me',
        ]);

        $authorizationUrl = $provider->getAuthorizationUrl([
            'scope' => 'identity submit read',
            'approval_prompt' => 'auto',
        ]);

        session(['oauth2state' => $provider->getState()]);

        return redirect($authorizationUrl);
    }


    public function callback(Request $request)
    {
        $provider = $this->getProvider();

        if ($request->get('state') !== session('oauth2state')) {
            session()->forget('oauth2state');
            return redirect()->route('dashboard')->with('error', 'Invalid OAuth state.');
        }

        try {
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            Auth::user()->update([
                'reddit_token' => $accessToken->getToken(),
                'reddit_refresh_token' => $accessToken->getRefreshToken(),
                'reddit_token_expires_at' => now()->addSeconds($accessToken->getExpires()),
            ]);

            return redirect()->route('dashboard')->with('success', 'Reddit account linked successfully!');
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            \Log::error('Reddit OAuth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('dashboard')->with('error', 'Failed to link Reddit account: ' . $e->getMessage());
        }
    }


    /**
     * Refresca el token de Reddit cuando expira.
     */
    public function refreshToken()
    {
        $provider = $this->getProvider();

        try {
            // Solicitar un nuevo token usando el refresh token
            $accessToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => Auth::user()->reddit_refresh_token,
            ]);

            // Actualizar los tokens en la base de datos
            Auth::user()->update([
                'reddit_token' => $accessToken->getToken(),
                'reddit_token_expires_at' => now()->addSeconds($accessToken->getExpires()),
            ]);

            return redirect()->route('dashboard')->with('success', 'Reddit token refreshed successfully!');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Failed to refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el proveedor OAuth2 para Reddit.
     */
    private function getProvider()
    {
        return new GenericProvider([
            'clientId'                => config('services.reddit.client_id'),
            'clientSecret'            => config('services.reddit.client_secret'),
            'redirectUri'             => config('services.reddit.redirect'),
            'urlAuthorize'            => 'https://www.reddit.com/api/v1/authorize',
            'urlAccessToken'          => 'https://www.reddit.com/api/v1/access_token',
            'urlResourceOwnerDetails' => 'https://oauth.reddit.com/api/v1/me',
        ]);
    }
}
