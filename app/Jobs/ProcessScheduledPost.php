<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ProcessScheduledPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    /**
     * Create a new job instance.
     */
    public function __construct($post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $user = \App\Models\User::find($this->post->user_id);

        if (!$user) {
            return;
        }

        $content = $this->post->content;

        // Publicar en la red social correspondiente
        if ($this->post->network === 'twitter' && $user->twitter_token && $user->twitter_token_secret) {
            $twitter = new \Abraham\TwitterOAuth\TwitterOAuth(
                config('services.twitter.api_key'),
                config('services.twitter.api_secret_key'),
                $user->twitter_token,
                $user->twitter_token_secret
            );

            $twitter->post('statuses/update', ['status' => $content]);
        }

        if ($this->post->network === 'mastodon' && $user->mastodon_token) {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . $user->mastodon_token,
            ])->post(config('services.mastodon.base_url') . '/api/v1/statuses', [
                'status' => $content,
            ]);
        }
    }
}

