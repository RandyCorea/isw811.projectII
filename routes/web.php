<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RedditController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MastodonController;
use App\Http\Controllers\TwitterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas necesarias para la aplicación. Incluye el manejo
| de perfiles, integración con Reddit y Mastodon, y gestión de publicaciones.
|
*/

// Redirigir la ruta raíz ('/') al login si el usuario no está autenticado
Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest');

// Ruta para el dashboard, accesible solo por usuarios autenticados
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas para usuarios autenticados
Route::middleware('auth')->group(function () {

    // Gestión del perfil del usuario
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::post('/enable2fa', [TwoFactorAuthController::class, 'enable'])->name('profile.enable2fa');
    });

    // Integración con Reddit
    Route::prefix('reddit')->group(function () {
        Route::get('/auth', [RedditController::class, 'redirect'])->name('reddit.auth');
        Route::get('/callback', [RedditController::class, 'callback'])->name('reddit.callback');
        Route::get('/refresh-token', [RedditController::class, 'refreshToken'])->name('reddit.refresh');
    });


    // Integración con Mastodon
    Route::prefix('mastodon')->group(function () {
        Route::get('/auth', [MastodonController::class, 'redirect'])->name('mastodon.auth');
        Route::get('/callback', [MastodonController::class, 'callback'])->name('mastodon.callback');
        Route::get('/manual', [MastodonController::class, 'manualCallbackForm'])->name('mastodon.manualCallbackForm');
        Route::post('/manual', [MastodonController::class, 'manualCallback'])->name('mastodon.manualCallback');
        Route::post('/post', [MastodonController::class, 'createPost'])->name('mastodon.post');
    });

    // Integración con Twitter
    Route::prefix('twitter')->group(function () {
        Route::get('/auth', [TwitterController::class, 'redirect'])->name('twitter.auth');
        Route::get('/callback', [TwitterController::class, 'callback'])->name('twitter.callback');
        Route::post('/tweet', [TwitterController::class, 'createTweet'])->name('twitter.tweet');
    });

    // Gestión de publicaciones
    Route::prefix('posts')->group(function () {
        Route::get('/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/publish', [PostController::class, 'publish'])->name('posts.publish');
        Route::get('/post', [PostController::class, 'index'])->name('post.index');

    });
    
        
});

// Cargar las rutas de autenticación generadas por Laravel Breeze
require __DIR__ . '/auth.php';
