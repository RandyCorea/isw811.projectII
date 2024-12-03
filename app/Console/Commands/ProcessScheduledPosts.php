<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class ProcessScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'posts:process-scheduled';

    /**
     * The console command description.
     */
    protected $description = 'Processes scheduled posts and publishes them if the time has arrived';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        \Log::info('Processing scheduled posts', ['now' => $now]);

        // Obtener publicaciones programadas que deban publicarse
        $scheduledPosts = DB::table('scheduled_posts')
            ->where('schedule_at', '<=', $now)
            ->where('processed', false) // Solo publicaciones no procesadas
            ->get();

        if ($scheduledPosts->isEmpty()) {
            \Log::info('No scheduled posts found to process', ['time' => $now]);
            return;
        }

        foreach ($scheduledPosts as $post) {
            \Log::info('Processing post', [
                'id' => $post->id,
                'user_id' => $post->user_id,
                'content' => $post->content,
                'network' => $post->network,
            ]);

            $user = User::find($post->user_id);

            if (!$user) {
                \Log::error('User not found for post', ['post_id' => $post->id]);
                continue;
            }

            try {
                // Publicar según la red social
                if ($post->network === 'twitter') {
                    $this->postToTwitter($user, $post);
                } elseif ($post->network === 'mastodon') {
                    $this->postToMastodon($user, $post);
                }

                // Marcar como procesado
                DB::table('scheduled_posts')->where('id', $post->id)->update(['processed' => true]);

                \Log::info('Post successfully processed', ['post_id' => $post->id]);
            } catch (\Exception $e) {
                \Log::error('Error processing post', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Scheduled posts processed successfully.');
    }

    /**
     * Publicar en Twitter.
     */
    protected function postToTwitter($user, $post)
    {
        if (!$user->twitter_token || !$user->twitter_token_secret) {
            \Log::error('Twitter tokens not found for user', ['user_id' => $user->id]);
            return;
        }

        \Log::info('Posting to Twitter', ['content' => $post->content]);

        $twitter = new \Abraham\TwitterOAuth\TwitterOAuth(
            config('services.twitter.api_key'),
            config('services.twitter.api_secret_key'),
            $user->twitter_token,
            $user->twitter_token_secret
        );

        $response = $twitter->post('statuses/update', ['status' => $post->content]);

        if (isset($response->errors)) {
            \Log::error('Twitter API error', ['errors' => $response->errors]);
            throw new \Exception('Twitter API error: ' . $response->errors[0]->message);
        }

        \Log::info('Post successfully published on Twitter', ['response' => $response]);
    }

    /**
     * Publicar en Mastodon.
     */
    protected function postToMastodon($user, $post)
    {
        if (!$user->mastodon_token) {
            \Log::error('Mastodon token not found for user', ['user_id' => $user->id]);
            return;
        }

        \Log::info('Posting to Mastodon', ['content' => $post->content]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->mastodon_token,
        ])->post(config('services.mastodon.base_url') . '/api/v1/statuses', [
            'status' => $post->content,
        ]);

        if (!$response->successful()) {
            \Log::error('Mastodon API error', ['response' => $response->json()]);
            throw new \Exception('Mastodon API error: ' . ($response->json('error') ?? 'Unknown error'));
        }

        \Log::info('Post successfully published on Mastodon', ['response' => $response->json()]);
    }
}
