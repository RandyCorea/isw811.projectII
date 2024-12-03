<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Posts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- Logs en el frontend -->
            <script>
                console.log("Twitter Posts Data:", @json($twitterPosts));
                console.log("Mastodon Posts Data:", @json($mastodonPosts));
            </script>
            <!-- Listado de publicaciones de Twitter -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="font-bold text-2xl text-gray-800 dark:text-gray-100 mb-4">
                    X Posts
                </h3>
                @if (!empty($twitterPosts))
                    <div class="space-y-4">
                        @foreach ($twitterPosts as $post)
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                                <!-- Texto del Tweet -->
                                <p class="text-lg text-gray-800 dark:text-white mb-2">
                                    {{ $post->text }}
                                </p>
                                <!-- Fecha del Tweet -->
                                <small class="block text-gray-500 dark:text-green-400 mb-2">
                                    Posted on: {{ \Carbon\Carbon::parse($post->created_at)->format('M d, Y h:i A') }}
                                </small>
                                <!-- Métricas del Tweet -->
                                <div class="flex items-center space-x-6 text-gray-600 dark:text-gray-400">
                                    <span><i class="fa fa-heart"></i> {{ $post->public_metrics->like_count }} Likes</span>
                                    <span><i class="fa fa-retweet"></i> {{ $post->public_metrics->retweet_count }} Retweets</span>
                                    <span><i class="fa fa-reply"></i> {{ $post->public_metrics->reply_count }} Replies</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No Twitter posts available.</p>
                @endif
            </div>

            <!-- Listado de publicaciones de Mastodon -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
                <h3 class="font-bold text-2xl text-gray-800 dark:text-gray-100 mb-4">
                    Mastodon Posts
                </h3>
                @if (!empty($mastodonPosts))
                    <div class="space-y-4">
                        @foreach ($mastodonPosts as $post)
                            <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                                <!-- Contenido del Post -->
                                <div class="prose dark:prose-invert mb-2">
                                    {!! $post['content'] !!}
                                </div>
                                <!-- Fecha del Post -->
                                <small class="block text-gray-500 dark:text-green-400 mb-2">
                                    Posted on: {{ \Carbon\Carbon::parse($post['created_at'])->format('M d, Y h:i A') }}
                                </small>
                                <!-- Métricas del Post -->
                                <div class="flex items-center space-x-6 text-gray-600 dark:text-gray-400">
                                    <span><i class="fa fa-heart"></i> {{ $post['favourites_count'] ?? 0 }} Likes</span>
                                    <span><i class="fa fa-retweet"></i> {{ $post['reblogs_count'] ?? 0 }} Reblogs</span>
                                    <span><i class="fa fa-reply"></i> {{ $post['replies_count'] ?? 0 }} Replies</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No Mastodon posts available.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
