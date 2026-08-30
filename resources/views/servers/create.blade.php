<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Add HestiaCP Server</h2>
                <p class="mt-1 text-sm text-gray-600">Register a new server sites can be provisioned on</p>
            </div>
            <a href="{{ route('servers.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition duration-150">
                Back to Servers
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('servers.store') }}" class="space-y-6">
                @csrf
                @include('servers._form')

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('servers.index') }}" class="inline-flex items-center px-4 py-2 bg-yellow-400 hover:bg-gray-800 text-white font-semibold rounded-lg shadow-lg transition duration-150">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-black hover:bg-gray-800 text-white font-semibold rounded-lg shadow-lg transition duration-150">
                        Add Server
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
