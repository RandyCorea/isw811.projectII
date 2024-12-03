<?php

namespace App\Http\Controllers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;


class PostController extends Controller
{
    public function index()
    {
        $twitterPosts = $this->getTwitterPosts();
        $mastodonPosts = $this->getMastodonPosts(Auth::user()->mastodon_token);

        return view('posts.index', compact('twitterPosts', 'mastodonPosts'));
    }
    private function getTwitterPosts()
    {
        $bearerToken = config('services.twitter.bearer_token');

        // Verificar si el Bearer Token está configurado
        if (!$bearerToken) {
            Log::error('Bearer Token no configurado.');
            return [];
        }

        try {
            // Inicializar el cliente HTTP
            $client = new Client(['base_uri' => 'https://api.twitter.com/2/']);

            // Configurar las cabeceras
            $headers = [
                'Authorization' => 'Bearer ' . $bearerToken,
            ];

            // Obtener el ID del usuario autenticado (reemplaza "your_user_id" si ya lo conoces)
            $responseUser = $client->get('users/by/username/{YOUR_USERNAME}', [
                'headers' => $headers,
            ]);
            $userData = json_decode($responseUser->getBody(), true);

            // Verificar si el ID del usuario existe
            if (!isset($userData['data']['id'])) {
                Log::error('No se pudo obtener el ID del usuario desde la API de Twitter.', $userData);
                return [];
            }

            $userId = $userData['data']['id']; // ID del usuario autenticado

            // Obtener los tweets del usuario
            $responseTweets = $client->get("users/{$userId}/tweets", [
                'headers' => $headers,
                'query' => [
                    'max_results' => 10, // Número máximo de tweets
                    'tweet.fields' => 'created_at,text,public_metrics', // Campos adicionales de los tweets
                ],
            ]);
            $tweetsData = json_decode($responseTweets->getBody(), true);

            if (!isset($tweetsData['data'])) {
                Log::error('No se pudieron obtener tweets del usuario.', (array) $tweetsData);
                return [];
            }

            // Procesar los tweets y devolverlos
            return collect($tweetsData['data'])->map(function ($tweet) {
                return [
                    'text' => $tweet['text'],
                    'created_at' => $tweet['created_at'],
                    'retweet_count' => $tweet['public_metrics']['retweet_count'] ?? 0,
                    'like_count' => $tweet['public_metrics']['like_count'] ?? 0,
                ];
            });
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::error('Error de cliente al obtener publicaciones de Twitter: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error general al obtener publicaciones de Twitter: ' . $e->getMessage());
        }

        return [];
    }


    private function getMastodonPosts($mastodonToken)
    {
        if (!$mastodonToken) {
            Log::info('El usuario no tiene token de Mastodon.');
            return [];
        }

        try {
            $verifyResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $mastodonToken,
            ])->get(config('services.mastodon.base_url') . '/api/v1/accounts/verify_credentials');

            if (!$verifyResponse->successful()) {
                Log::error('Error verificando credenciales de Mastodon.', $verifyResponse->json());
                return [];
            }

            $userId = $verifyResponse->json('id');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $mastodonToken,
            ])->get(config('services.mastodon.base_url') . "/api/v1/accounts/$userId/statuses?limit=10");

            if ($response->successful()) {
                return collect($response->json())->map(function ($post) {
                    return [
                        'content' => $post['content'],
                        'created_at' => $post['created_at'],
                        'favourites_count' => $post['favourites_count'] ?? 0,
                        'reblogs_count' => $post['reblogs_count'] ?? 0,
                        'replies_count' => $post['replies_count'] ?? 0,
                    ];
                });
            } else {
                Log::error('Mastodon API Error:', $response->json());
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener publicaciones de Mastodon: ' . $e->getMessage());
        }

        return [];
    }


    public function publish(Request $request)
    {
        $request->validate([
            'content' => 'required|max:280',
            'networks' => 'required|array',
            'schedule' => 'nullable|date|after:now',
        ]);

        $content = $request->input('content');
        $networks = $request->input('networks');
        $schedule = $request->input('schedule');

        if ($schedule) {
            foreach ($networks as $network) {
                DB::table('scheduled_posts')->insert([
                    'user_id' => Auth::id(),
                    'content' => $content,
                    'network' => $network,
                    'schedule_at' => $schedule,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'message' => 'Post scheduled successfully!',
            ]);
        }

        return $this->publishNow($content, $networks);
    }

    protected function publishNow($content, $networks)
    {
        $response = [
            'message' => 'Post published successfully!',
            'errors' => [],
        ];

        if (in_array('twitter', $networks)) {
            $twitterError = $this->publishToTwitter($content);
            if ($twitterError) {
                $response['errors']['twitter'] = $twitterError;
            }
        }

        if (in_array('mastodon', $networks)) {
            $mastodonError = $this->publishToMastodon($content);
            if ($mastodonError) {
                $response['errors']['mastodon'] = $mastodonError;
            }
        }

        if (!empty($response['errors'])) {
            $response['message'] = 'Some errors occurred.';
        }

        return response()->json($response);
    }

    private function publishToTwitter($content)
    {
        try {
            $tokens = Auth::user();
            if (!$tokens->twitter_token || !$tokens->twitter_token_secret) {
                return 'Twitter account is not linked.';
            }

            $connection = new TwitterOAuth(
                config('services.twitter.api_key'),
                config('services.twitter.api_secret_key'),
                $tokens->twitter_token,
                $tokens->twitter_token_secret
            );

            $twitterResponse = $connection->post('tweets', ['text' => $content]);
            $httpCode = $connection->getLastHttpCode();

            if ($httpCode != 201) {
                Log::error('Twitter API Error:', (array) $twitterResponse);
                return 'Failed to post tweet. HTTP Code: ' . $httpCode;
            }

            return null; // No error
        } catch (\Exception $e) {
            Log::error('Twitter API Exception: ' . $e->getMessage());
            return 'Exception while posting on Twitter: ' . $e->getMessage();
        }
    }

    private function publishToMastodon($content)
    {
        try {
            $token = Auth::user()->mastodon_token;
            if (!$token) {
                return 'Mastodon account is not linked.';
            }

            $mastodonResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post(config('services.mastodon.base_url') . '/api/v1/statuses', [
                'status' => $content,
            ]);

            if (!$mastodonResponse->successful()) {
                Log::error('Mastodon API Error:', $mastodonResponse->json());
                return 'Failed to post on Mastodon.';
            }

            return null; // No error
        } catch (\Exception $e) {
            Log::error('Mastodon API Exception: ' . $e->getMessage());
            return 'Exception while posting on Mastodon: ' . $e->getMessage();
        }
    }
}
