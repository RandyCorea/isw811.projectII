<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
            </label>
        </div>

        <!-- Campo de 2FA -->
        @if (session('2fa_required'))
        <div class="mt-4">
            <x-input-label for="2fa_code" :value="__('Two-Factor Authentication Code')" />
            <x-text-input id="2fa_code" class="block mt-1 w-full" type="text" name="2fa_code" required autofocus />
            <x-input-error :messages="$errors->get('2fa_code')" class="mt-2" />
        </div>
        @endif


        <div class="flex items-center justify-end mt-4">
            <!-- Link to Register -->
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('register') }}">
                {{ __('Don\'t have an account?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Login') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>