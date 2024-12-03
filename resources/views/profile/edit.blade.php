<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Sección de Two-Factor Authentication -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('Two-Factor Authentication') }}
                    </h3>

                    <div class="mt-4">
                        @if ($user->google2fa_secret)
                        <p class="text-green-500 font-medium">✅ 2FA is enabled for your account.</p>
                        <form action="{{ route('profile.disable2fa') }}" method="POST" class="mt-4">
                            @csrf
                            <button type="submit"
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Disable 2FA
                            </button>
                        </form>
                        @else
                        <p class="text-red-500 font-medium">⚠️ 2FA is not enabled for your account.</p>                        
                        <div class="mt-4">
                            <p class="text-gray-500 dark:text-gray-400">Scan the QR code below using Google Authenticator:</p>
                            @if (!empty($qrCode))
                            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" class="my-4">
                            @else
                            <p class="text-red-500">Unable to generate QR Code. Please check your configuration.</p>
                            @endif
                        </div>

                        <form action="{{ route('profile.enable2fa') }}" method="POST">
                            @csrf
                            <input type="hidden" name="secret" value="{{ $secret }}">
                            <div class="mt-4">
                                <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Verification Code
                                </label>
                                <input type="text" name="code" id="code"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <button type="submit"
                                class="w-full mt-4 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Enable 2FA
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sección de Profile Information -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Sección de Update Password -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Sección de Delete User -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>