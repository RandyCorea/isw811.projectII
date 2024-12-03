<x-guest-layout>
    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <h1 class="text-xl font-bold mb-4">Two-Factor Authentication</h1>
        <p class="mb-4">Please enter the code from your Google Authenticator app.</p>
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700">Verification Code</label>
            <input id="code" name="code" type="text" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('code')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        <div class="mt-4">
            <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Verify Code
            </button>
        </div>
    </form>
</x-guest-layout>
