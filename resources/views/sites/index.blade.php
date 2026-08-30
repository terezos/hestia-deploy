<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Site Management</h2>
                <p class="mt-1 text-sm text-gray-600">Manage and monitor all your deployed sites</p>
            </div>
            <a href="{{ route('sites.create') }}"
               class="inline-flex items-center px-4 py-2 bg-black hover:bg-gray-800 text-white font-semibold rounded-lg shadow-lg transition duration-150 ease-in-out transform hover:scale-155">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Deploy New
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-[1600px] mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Search and Filter Form -->
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <form method="GET" action="{{ route('sites.index') }}" class="flex flex-col md:flex-row gap-4">
                    <!-- Search Input -->
                    <div class="flex-1">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Domain</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   name="search"
                                   id="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search by domain..."
                                   class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                        </div>
                    </div>

                    <!-- Framework Filter -->
                    <div class="w-full md:w-64">
                        <label for="framework" class="block text-sm font-medium text-gray-700 mb-2">Framework</label>
                        <select name="framework"
                                id="framework"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                            <option value="">All Frameworks</option>
                            <option value="wordpress" {{ request('framework') === 'wordpress' ? 'selected' : '' }}>
                                WordPress
                            </option>
                            <option value="opencart_octopus" {{ request('framework') === 'opencart_octopus' ? 'selected' : '' }}>
                                Opencart - Octopus
                            </option>
                            <option value="opencart_default" {{ request('framework') === 'opencart_default' ? 'selected' : '' }}>
                                Opencart - Default
                            </option>
                            <option value="laravel" {{ request('framework') === 'laravel' ? 'selected' : '' }}>
                                Laravel
                            </option>
                        </select>
                    </div>

                    <!-- Server Filter -->
                    <div class="w-full md:w-64">
                        <label for="hestia_server_id" class="block text-sm font-medium text-gray-700 mb-2">Server</label>
                        <select name="hestia_server_id"
                                id="hestia_server_id"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                            <option value="">All Servers</option>
                            @foreach ($servers as $server)
                                <option value="{{ $server->id }}" {{ (string) request('hestia_server_id') === (string) $server->id ? 'selected' : '' }}>
                                    {{ $server->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Filter
                        </button>
                        @if(request('search') || request('framework'))
                            <a href="{{ route('sites.index') }}"
                               class="inline-flex items-center px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if($sites->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         style="width: 100px; margin-top: 10px">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="mt-4 text-xl font-semibold text-gray-900">No sites deployed yet</h3>
                    <p class="mt-2 text-gray-600">Get started by deploying your first site to the cloud</p>
                    <a href="{{ route('sites.create') }}"
                       class="mt-6 inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-black font-semibold rounded-lg shadow-md transition duration-150 mb-5">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Deploy Your First
                    </a>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Domain
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Server
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Framework
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    PHP
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    SSL
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Created
                                </th>
                                <th scope="col"
                                    class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                            @foreach($sites as $site)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('sites.show', $site) }}"
                                           class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                            &nbsp;{{ $site->domain }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">
                                        {{ $site->hestiaServer->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                            {{ $site->framework === 'wordpress' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ ucwords(str_replace('_', ' ', $site->framework)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($site->status === 'active')
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                <span
                                                    class="w-2 h-2 mr-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                                Active
                                            </span>
                                        @elseif($site->status === 'provisioning')
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                <span
                                                    class="w-2 h-2 mr-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                                                Provisioning
                                            </span>
                                        @elseif($site->status === 'failed')
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                <span class="w-2 h-2 mr-1.5 bg-red-500 rounded-full"></span>
                                                Failed
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                {{ ucfirst($site->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center text-sm text-gray-700 font-medium">
                                            <svg class="w-4 h-4 mr-1.5 text-indigo-500" fill="currentColor"
                                                 viewBox="0 0 20 20">
                                                <path
                                                    d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                            </svg>
                                            PHP {{ $site->php_version }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($site->ssl_enabled)
                                            <span
                                                class="inline-flex items-center justify-center w-8 h-8 bg-green-100 rounded-full"
                                                title="SSL Enabled">
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2"
                                                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center justify-center w-8 h-8 bg-red-100 rounded-full"
                                                title="SSL Disabled">
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                                     viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                        {{ $site->created_at->format('M d, Y') }}
                                        <span
                                            class="text-xs text-gray-400 block">{{ $site->created_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                        <a href="{{ route('sites.show', $site) }}"
                                           class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-lg transition duration-150">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            View
                                        </a>
                                        <a href="https://{{ $site->domain }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium text-sm rounded-lg transition duration-150">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            Visit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="max-w-[1600px] mx-auto py-2 px-3 sm:px-2 lg:px-3">
                            {{ !!! $sites->isEmpty() ? $sites->withQueryString()->links() : '' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
