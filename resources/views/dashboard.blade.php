@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-app-layout>
    <x-slot name="header">
        <nav class="flex justify-between items-center">
            <!-- Menú superior con opciones -->
            <div class="flex items-center space-x-6">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}" class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Dashboard') }}
                </a>
            </div>
        </nav>
    </x-slot>


    <!-- Barra de navegación para redes sociales -->
    <div class="bg-gray-200 dark:bg-gray-700 py-4 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-center items-center space-x-8">
            @if (Auth::check())
                <!-- Twitter/X -->
                <div class="flex flex-col items-center space-y-2">
                    <a href="{{ Auth::user()->twitter_token ? '#' : route('twitter.auth') }}"
                        class="flex flex-col items-center hover:scale-105 transition-transform"
                        title="{{ Auth::user()->twitter_token ? 'Linked' : 'Not Linked' }}">
                        <!-- Contenedor de la imagen -->
                        <div class="h-16 w-16 flex justify-center items-center rounded-full bg-gray-100 dark:bg-gray-600 shadow-md">
                            <img src="https://static.vecteezy.com/system/resources/previews/042/148/611/non_2x/new-twitter-x-logo-twitter-icon-x-social-media-icon-free-png.png"
                                alt="Twitter"
                                class="h-12 w-12 object-contain">
                        </div>
                        <!-- Texto dinámico -->
                        <span class="mt-2 text-sm font-semibold {{ Auth::user()->twitter_token ? 'text-green-500' : 'text-red-500' }}">
                            {{ Auth::user()->twitter_token ? 'Linked' : 'Not Linked' }}
                        </span>
                    </a>
                </div>

                <!-- Mastodon -->
                <div class="flex flex-col items-center space-y-2">
                    <a href="{{ Auth::user()->mastodon_token ? '#' : route('mastodon.auth') }}"
                        class="flex flex-col items-center hover:scale-105 transition-transform"
                        title="{{ Auth::user()->mastodon_token ? 'Linked' : 'Not Linked' }}">
                        <!-- Contenedor de la imagen -->
                        <div class="h-16 w-16 flex justify-center items-center rounded-full bg-gray-100 dark:bg-gray-600 shadow-md">
                            <img src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/mastodon-icon.png"
                                alt="Mastodon"
                                class="h-12 w-12 object-contain">
                        </div>
                        <!-- Texto dinámico -->
                        <span class="mt-2 text-sm font-semibold {{ Auth::user()->mastodon_token ? 'text-green-500' : 'text-red-500' }}">
                            {{ Auth::user()->mastodon_token ? 'Linked' : 'Not Linked' }}
                        </span>
                    </a>
                </div>
            @endif
        </div>
    </div>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Crear publicaciones -->
            <div class="md:col-span-3 bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-6">
                <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-100">Create Post</h3>
                <form id="postForm" action="{{ route('posts.publish') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Contenido del post -->
                    <div>
                        <label for="postContent" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Post Content</label>
                        <textarea id="postContent" name="content" rows="4" class="block w-full mt-1 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 dark:bg-gray-900 text-indigo-700 dark:text-indigo-300" placeholder="What's on your mind?" required></textarea>
                    </div>

                    <!-- Redes sociales -->
                    <div>
                        <label for="networks" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Networks</label>
                        <div class="mt-2 flex flex-col gap-2">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="networks[]" value="twitter" class="form-checkbox" {{ Auth::check() && Auth::user()->twitter_token ? '' : 'disabled' }}>
                                <span class="text-white">X</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="networks[]" value="mastodon" class="form-checkbox" {{ Auth::check() && Auth::user()->mastodon_token ? '' : 'disabled' }}>
                                <span class="text-white">Mastodon</span>
                            </label>

                        </div>
                    </div>

                    <!-- Programar publicación -->
                    <div>
                        <label for="schedule" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Schedule Post (optional)</label>
                        <input type="datetime-local" id="schedule" name="schedule"
                            class="block w-full mt-1 rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 dark:bg-gray-900 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Leave blank to post immediately.</p>
                    </div>

                    <button type="button" id="submitPost" class="w-full px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-500">
                        Submit Post
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 Integration -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('submitPost').addEventListener('click', async function() {
            const form = document.getElementById('postForm');
            const formData = new FormData(form);

            try {
                const response = await fetch("{{ route('posts.publish') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const result = await response.json();

                if (response.ok && result.errors.length === 0) {
                    Swal.fire({
                        icon: 'success',
                        title: result.message,
                        timer: 3000,
                        showConfirmButton: false
                    });

                    // Limpiar formulario después de éxito
                    form.reset();
                } else {
                    console.error('Errors:', result.errors);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to publish post.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            } catch (error) {
                console.error('Unexpected error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An unexpected error occurred.',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        });
    </script>
</x-app-layout>
