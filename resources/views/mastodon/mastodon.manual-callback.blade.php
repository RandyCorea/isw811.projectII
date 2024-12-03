<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mastodon Manual Callback</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-semibold mb-4">Enter Authorization Code</h3>
                    <form action="{{ route('mastodon.manualCallback') }}" method="POST">
                        @csrf
                        <label for="authorization_code" class="block text-sm font-medium text-gray-700">Authorization Code</label>
                        <input type="text" name="authorization_code" id="authorization_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded shadow hover:bg-indigo-500">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
