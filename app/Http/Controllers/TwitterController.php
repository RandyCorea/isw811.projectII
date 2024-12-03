<?php

namespace App\Http\Controllers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TwitterController extends Controller
{
    /**
     * Redirige al usuario a la página de autorización de Twitter.
     */
    public function redirect()
    {
        try {
            $connection = new TwitterOAuth(
                config('services.twitter.api_key'),
                config('services.twitter.api_secret_key')
            );

            // Solicitar un token de solicitud (request token)
            $requestToken = $connection->oauth('oauth/request_token', [
                'oauth_callback' => config('services.twitter.callback_url'),
            ]);

            if (!$requestToken || isset($requestToken['errors'])) {
                \Log::error('Error obtaining request token from Twitter.', ['errors' => $requestToken['errors'] ?? 'Unknown error']);
                return redirect()->route('dashboard')->with('error', 'Failed to initiate Twitter authentication.');
            }

            // Guardar los tokens en la sesión
            session([
                'oauth_token' => $requestToken['oauth_token'],
                'oauth_token_secret' => $requestToken['oauth_token_secret'],
            ]);

            // Generar la URL de autorización
            $url = $connection->url('oauth/authorize', [
                'oauth_token' => $requestToken['oauth_token'],
            ]);

            return redirect($url);
        } catch (\Exception $e) {
            \Log::error('Error during Twitter redirect.', ['exception' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'An error occurred during Twitter authentication.');
        }
    }

    /**
     * Procesa el callback después de la autorización.
     */
    public function callback(Request $request)
    {
        try {
            if (!$request->has('oauth_token') || !$request->has('oauth_verifier')) {
                \Log::warning('Callback request missing required parameters.', $request->all());
                return redirect()->route('dashboard')->with('error', 'Missing callback parameters.');
            }

            $connection = new TwitterOAuth(
                config('services.twitter.api_key'),
                config('services.twitter.api_secret_key'),
                session('oauth_token'),
                session('oauth_token_secret')
            );

            // Solicitar el token de acceso
            $accessToken = $connection->oauth('oauth/access_token', [
                'oauth_verifier' => $request->get('oauth_verifier'),
            ]);

            if (!isset($accessToken['oauth_token']) || !isset($accessToken['oauth_token_secret'])) {
                \Log::error('Failed to obtain access token from Twitter.', $accessToken);
                return redirect()->route('dashboard')->with('error', 'Failed to retrieve Twitter access token.');
            }

            // Guardar el token en la base de datos
            Auth::user()->update([
                'twitter_token' => $accessToken['oauth_token'],
                'twitter_token_secret' => $accessToken['oauth_token_secret'],
            ]);

            return redirect()->route('dashboard')->with('success', 'Twitter account linked successfully!');
        } catch (\Exception $e) {
            \Log::error('Error during Twitter callback.', ['exception' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'An error occurred while processing the Twitter callback.');
        }
    }

    /**
     * Publica un tweet usando el token del usuario.
     */
    public function createTweet(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->twitter_token || !$user->twitter_token_secret) {
                return response()->json(['status' => 'error', 'message' => 'Twitter account not linked.'], 400);
            }

            $connection = new TwitterOAuth(
                config('services.twitter.api_key'),
                config('services.twitter.api_secret_key'),
                $user->twitter_token,
                $user->twitter_token_secret
            );

            $content = $request->input('content');

            // Validar longitud del tweet
            if (strlen($content) > 280) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tweet exceeds the 280-character limit.',
                ], 400);
            }

            $response = $connection->post('statuses/update', ['status' => $content]);

            \Log::info('Twitter API headers:', $connection->getLastHttpHeaders());
            \Log::info('Twitter API response:', (array)$response);

            if (isset($response->errors)) {
                \Log::error('Twitter API Errors:', (array)$response->errors);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to post tweet: ' . ($response->errors[0]->message ?? 'Unknown error'),
                    'debug' => (array)$response,
                ], 500);
            }

            if (isset($response->id)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tweet posted successfully!',
                    'debug' => (array)$response,
                ]);
            }

            \Log::warning('Unexpected response from Twitter API.', (array)$response);

            return response()->json([
                'status' => 'error',
                'message' => 'Unexpected response from Twitter API.',
                'debug' => (array)$response,
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Exception while posting tweet.', ['exception' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while posting the tweet.',
                'debug' => ['exception' => $e->getMessage()],
            ], 500);
        }
    }
}
