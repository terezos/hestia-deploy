<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $site->domain }}</h2>
                <p class="mt-1 text-sm text-gray-600">Site Details and Configuration</p>
            </div>
            <a href="{{ route('sites.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                &nbsp;Back to Sites
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{-- Site Overview Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    &nbsp;Site Overview
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if($site->status === 'active')
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($site->status === 'provisioning')
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($site->status === 'failed')
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($site->status === 'suspended')
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</p>
                            <div class="mt-2 flex items-center justify-between">
                                <div id="statusBadge">
                                    @if($site->status === 'active')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                            <span class="w-1.5 h-1.5 mr-2 bg-green-500 rounded-full animate-pulse"></span>
                                            Active
                                        </span>
                                    @elseif($site->status === 'provisioning')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                            <span class="w-1.5 h-1.5 mr-2 bg-yellow-500 rounded-full animate-pulse"></span>
                                            Provisioning
                                        </span>
                                    @elseif($site->status === 'failed')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                            <span class="w-1.5 h-1.5 mr-2 bg-red-500 rounded-full"></span>
                                            Failed
                                        </span>
                                    @elseif($site->status === 'suspended')
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 border border-orange-200">
                                            <span class="w-1.5 h-1.5 mr-2 bg-orange-500 rounded-full"></span>
                                            Suspended
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                            {{ ucfirst($site->status) }}
                                        </span>
                                    @endif
                                </div>
                                @if($site->status === 'failed')
                                    <button onclick="retryProvisioning({{ $site->id }})"
                                            id="retryBtn"
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-150 hover:shadow-md transform hover:scale-105">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Retry
                                    </button>
                                @endif
                                @role('admin')
                                @if($site->status === 'active' || $site->status === 'suspended')
                                    <button onclick="changeStatus({{ $site->id }}, '{{ $site->status }}')"
                                            id="changeStatusBtn"
                                            class="inline-flex items-center px-3 py-1.5 {{ $site->status === 'active' ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700' }} text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-150 hover:shadow-md transform hover:scale-105">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            @if($site->status === 'active')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            @endif
                                        </svg>
                                        {{ $site->status === 'active' ? 'Suspend' : 'Unsuspend' }}
                                    </button>
                                @endif
                                @endrole
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Framework</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $site->framework)) }}</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">PHP Version</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900">PHP {{ $site->php_version }}</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if($site->ssl_enabled)
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">SSL Certificate</p>
                                    <p class="mt-1 text-sm font-semibold {{ $site->ssl_enabled ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $site->ssl_enabled ? 'Enabled' : 'Disabled' }}
                                    </p>
                                </div>
                                @role('admin')
                                <button onclick="renewSSL({{ $site->id }})"
                                        id="renewSSLBtn"
                                        class="ml-4 inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg shadow-sm transition duration-150">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    @if($site->ssl_enabled)
                                        Renew
                                    @else
                                        Create
                                    @endif
                                </button>
                                @endrole
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Created on {{ $site->created_at->format('F j, Y \a\t g:i A') }}</span>
                        <span class="mx-2 text-gray-400">•</span>
                        <span>{{ $site->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            @if($site->status === 'active')
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="https://{{ $site->domain }}" target="_blank"
                           class="flex items-center justify-center px-6 py-4 bg-white hover:bg-gray-50 border-2 border-blue-300 text-blue-700 font-semibold rounded-lg shadow-sm transition duration-150 transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            Visit Site
                        </a>
                        <a href="https://{{ $site->domain }}/admin" target="_blank"
                           class="flex items-center justify-center px-6 py-4 bg-white hover:bg-gray-50 border-2 border-blue-300 text-blue-700 font-semibold rounded-lg shadow-sm transition duration-150 transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Admin Panel
                        </a>
                        <a href="https://{{ $site->domain }}/phpmyadmin" target="_blank"
                           class="flex items-center justify-center px-6 py-4 bg-white hover:bg-gray-50 border-2 border-blue-300 text-blue-700 font-semibold rounded-lg shadow-sm transition duration-150 transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                            </svg>
                            phpMyAdmin
                        </a>
                    </div>
                </div>
            @endif

            {{-- Repository Configuration --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    &nbsp;Repository Configuration
                </h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                    <div class="flex items-start mb-3">
                        <span class="text-sm font-medium text-black w-24 px-3 py-1 flex-shrink-0"><strong>Repository:</strong></span>
                        <div class="flex items-center flex-1 bg-white px-3 py-1 rounded border border-gray-200">
                            <span class="text-sm text-gray-900 font-mono break-all flex-1">{{ $site->repo_url }}</span>
                            <button onclick="copyToClipboard('{{ $site->repo_url }}', this)" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-start mb-3">
                        <span class="text-sm font-medium text-black w-24 px-3 py-1 flex-shrink-0"><strong>Branch:</strong></span>
                        <div class="flex-1">
                            <div class="flex items-center gap-2" id="branchViewRow">
                                <span id="branchDisplay" class="text-sm text-gray-900 font-mono bg-white px-3 py-1 rounded border border-gray-200 break-all">{{ $site->branch }}</span>
                                @role('admin')
                                <button type="button" onclick="openBranchEditor()" id="changeBranchBtn"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-semibold rounded-lg transition duration-150">
                                    Change Branch
                                </button>
                                @endrole
                            </div>
                            <div id="branchEditRow" class="hidden items-center gap-2 mt-2">
                                <input type="text" id="newBranchInput" value="{{ $site->branch }}" autocomplete="off"
                                       class="flex-1 border-2 border-gray-300 rounded-lg px-3 py-1.5 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" onclick="submitBranchChange({{ $site->id }})" id="confirmBranchBtn"
                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition duration-150 whitespace-nowrap">
                                    Fetch &amp; Deploy
                                </button>
                                <button type="button" onclick="closeBranchEditor()" class="text-xs text-gray-500 hover:text-gray-700 whitespace-nowrap">Cancel</button>
                            </div>
                            <p id="branchChangeHint" class="text-xs text-red-600 mt-1 hidden"></p>
                        </div>
                    </div>
                    <div class="flex items-start mb-3">
                        <span class="text-sm font-medium text-black w-24 px-3 py-1 flex-shrink-0"><strong>Deploy Key:</strong></span>
                        <div class="flex-1">
                            <button type="button" onclick="showDeployKey('{{ $site->domain }}', {{ $site->hestia_server_id }})" id="deployKeyBtn"
                                    class="inline-flex items-center px-3 py-1.5 bg-orange-100 hover:bg-orange-200 text-orange-700 text-xs font-semibold rounded-lg transition duration-150">
                                Get / View Deploy Key
                            </button>
                            <div id="deployKeyBox" class="hidden mt-2 bg-white px-3 py-2 rounded border border-gray-200">
                                <textarea id="deployKeyText" readonly rows="4"
                                          class="w-full font-mono text-xs bg-gray-50 border border-gray-200 rounded p-2 resize-none"></textarea>
                                <button type="button" onclick="copyToClipboard(document.getElementById('deployKeyText').value, this)"
                                        class="mt-1 text-xs text-gray-500 hover:text-gray-700">Copy</button>
                                <p class="text-xs text-gray-500 mt-1">Add this as a deploy key in your repo (GitLab: Settings → Repository → Deploy Keys; GitHub: Settings → Deploy keys; Bitbucket: Repository settings → Access keys).</p>
                            </div>
                        </div>
                    </div>
                    @if($site->overwrite_suffix)
                    <div class="flex items-start">
                        <span class="text-sm font-medium text-black w-24 px-3 py-1 flex-shrink-0"><strong>Overwrite:</strong></span>
                        <span class="text-sm text-gray-900 font-mono bg-white px-3 py-1 rounded border border-gray-200">{{ $site->overwrite_suffix }}</span>
                    </div>
                    @endif
                    @if($site->status === 'active')
                        <div class="flex items-start">
                            <span class="text-sm font-medium text-black w-24 px-3 py-1 flex-shrink-0"><strong>Remote:</strong></span>
                            <div class="flex items-center flex-1 bg-white px-3 py-1 rounded border border-gray-200">
                                <span class="text-sm text-gray-900 font-mono break-all flex-1">git remote add @customName ssh://{{ $site->hestiaServer->ssh_user }}@{{ $site->hestiaServer->ssh_host }}/home/{{ $site->hestia_username }}/git/{{ $site->domain }}.git</span>
                                <button onclick="copyToClipboard('git remote add @customName ssh://{{ $site->hestiaServer->ssh_user }}@{{ $site->hestiaServer->ssh_host }}/home/{{ $site->hestia_username }}/git/{{ $site->domain }}.git', this)" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Deploy Webhook Configuration --}}
            @if($site->status === 'active')
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            &nbsp;Deploy Webhook
                        </h3>
                        <div
                            class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-lg p-4 border border-purple-200">
                            <p class="text-sm text-gray-700 mb-4">
                                Use this webhook URL in your repository settings to trigger automatic deployments
                                when you push code.
                            </p>
                            @if($site->webhook_token)
                            <div class="bg-white rounded-lg p-4 mb-4 border border-gray-300">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Webhook
                                    URL</label>
                                <div class="flex items-center">
                                    <button
                                        onclick="copyToClipboard('{{ url('/webhook/' . $site->id . '/' . $site->webhook_token) }}', this)"
                                        class="ml-2 text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                    &nbsp;
                                    <code
                                        class="text-sm font-mono text-gray-900 flex-1 break-all">{{ url('/webhook/' . $site->id . '/' . $site->webhook_token) }}</code>
                                    @role('admin')
                                    <button onclick="generateToken({{ $site->id }})"
                                            id="generate-token"
                                            class="ml-4 inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-700 text-white text-xs font-medium rounded-lg shadow-sm transition duration-150">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Refresh Token
                                    </button>
                                    @endrole
                                </div>
                            </div>
                            @else
                            <div class="bg-red-50 rounded-lg p-4 mb-4 border border-red-200 flex">
                                <p class="text-sm text-red-700 px-3 py-1.5">
                                    Webhook token is missing
                                </p>
                                <button onclick="generateToken({{ $site->id }})"
                                        id="generate-token"
                                        class="ml-4 inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-700 text-white text-xs font-medium rounded-lg shadow-sm transition duration-150">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Generate Token
                                </button>
                            </div>
                            @endif
                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">How to configure in GitLab:</h4>
                                <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1">
                                    <li>Go to your GitLab project → <strong>Settings</strong> →
                                        <strong>Webhooks</strong>
                                    </li>
                                    <li>Paste the webhook URL above</li>
                                    <li>Add Secret Token: <strong>HESTIACP</strong></li>
                                    <li>Select <strong>Push events</strong> trigger</li>
                                    <li>Optionally specify branch: <code
                                            class="bg-white px-2 py-0.5 rounded text-xs">{{ $site->branch }}</code></li>
                                    <li>Click <strong>Add webhook</strong></li>
                                </ol>
                            </div>
                        </div>
                    </div>
            @endif

            {{-- Database Credentials --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    Database Credentials
                </h3>
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Database Name</label>
                            <div class="flex items-center bg-white px-3 py-2 rounded border border-gray-300">
                                <code class="text-sm font-mono text-gray-900 flex-1">{{ $site->hestia_username }}_{{ $site->database_name }}</code>
                                <button onclick="copyToClipboard('{{ $site->hestia_username }}_{{ $site->database_name }}', this)" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Database User</label>
                            <div class="flex items-center bg-white px-3 py-2 rounded border border-gray-300">
                                <code class="text-sm font-mono text-gray-900 flex-1">{{ $site->hestia_username }}_{{ $site->database_user }}</code>
                                <button onclick="copyToClipboard('{{ $site->hestia_username }}_{{ $site->database_user }}', this)" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Database Password</label>
                            <div class="flex items-center bg-white px-3 py-2 rounded border border-gray-300">
                                <code class="text-sm font-mono text-gray-900 flex-1">{{ $site->database_password }}</code>
                                <button onclick="copyToClipboard('{{ $site->database_password }}', this)" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Database Host</label>
                            <div class="flex items-center bg-white px-3 py-2 rounded border border-gray-300">
                                <code class="text-sm font-mono text-gray-900 flex-1">localhost</code>
                                <button onclick="copyToClipboard('localhost', this)" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Backup Actions --}}
            @if($site->status === 'active')
            <div x-data="backupManager({{ $site->id }})" x-init="init()" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Backup Actions
                </h3>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-700 mb-4">
                            Queue a complete SQL dump of your database. Runs in the background — no timeout on large databases.
                        </p>
                        <button @click="requestBackup('database')" :disabled="requesting.database"
                                class="w-full inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-75 disabled:cursor-not-allowed text-white font-semibold rounded-lg shadow-md transition duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span x-text="requesting.database ? 'Queuing...' : 'Backup Database'"></span>
                        </button>
                    </div>
                    @if(in_array($site->framework, ['wordpress', 'opencart_octopus', 'opencart_default', 'laravel']))
                    <div>
                        <p class="text-sm text-gray-700 mb-4">
                            Queue a tar.gz archive of the site's images folder. Runs in the background — no timeout on large folders.
                        </p>
                        <button @click="requestBackup('images')" :disabled="requesting.images"
                                class="w-full inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-75 disabled:cursor-not-allowed text-white font-semibold rounded-lg shadow-md transition duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span x-text="requesting.images ? 'Queuing...' : 'Backup Images'"></span>
                        </button>
                    </div>
                    @endif
                </div>

                <div class="mt-4" x-show="backups.length > 0">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="py-2 pr-4">Type</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Size</th>
                                <th class="py-2 pr-4">Created</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="backup in backups" :key="backup.id">
                                <tr class="border-t border-gray-100">
                                    <td class="py-2 pr-4 capitalize" x-text="backup.type"></td>
                                    <td class="py-2 pr-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': backup.status === 'pending',
                                                  'bg-green-100 text-green-800': backup.status === 'completed',
                                                  'bg-red-100 text-red-800': backup.status === 'failed'
                                              }"
                                              x-text="backup.status"></span>
                                    </td>
                                    <td class="py-2 pr-4" x-text="formatSize(backup.size)"></td>
                                    <td class="py-2 pr-4" x-text="new Date(backup.created_at).toLocaleString()"></td>
                                    <td class="py-2 pr-4 text-right whitespace-nowrap">
                                        <a :href="'/sites/{{ $site->id }}/backups/' + backup.id + '/download'"
                                           x-show="backup.status === 'completed'"
                                           class="text-blue-600 hover:text-blue-800 font-medium mr-3">Download</a>
                                        <button @click="deleteBackup(backup.id)" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($site->status === 'active')
                <div x-data="serverLogViewer({{ $site->id }})" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        &nbsp;Server Logs
                    </h3>
                    <div class="flex flex-wrap items-end gap-3 mb-4">
                        <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                            <button @click="type = 'access'" type="button"
                                    class="px-4 py-2 text-sm font-medium"
                                    :class="type === 'access' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'">
                                Access Log
                            </button>
                            <button @click="type = 'error'" type="button"
                                    class="px-4 py-2 text-sm font-medium border-l border-gray-300"
                                    :class="type === 'error' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'">
                                Error Log
                            </button>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Lines</label>
                            <input type="number" x-model.number="lines" min="1" max="5000"
                                   class="w-24 rounded-lg border-gray-300 text-sm">
                        </div>
                        <button @click="fetchLog()" :disabled="loading" type="button"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-75 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-md transition duration-150">
                            <span x-text="loading ? 'Loading...' : 'Fetch'"></span>
                        </button>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto" style="background: #000; padding: 10px">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap bg-black" x-text="content || 'No log loaded yet.'" style="max-height: 400px; overflow-y: auto; padding: 2px;"></pre>
                    </div>
                </div>
            @endif

            {{-- Provisioning Log --}}
            <div x-data="logUpdater({{ $site->id }}, '{{ $site->status }}')" x-init="init()" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    &nbsp;Provisioning Log
                </h3>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto h-64" style="background: #000; padding: 10px">
                    <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap bg-black" x-text="log" style="max-height: 400px;padding: 2px;"></pre>
                </div>
            </div>

            @role('admin')
            <div class="bg-white rounded-xl shadow-sm border-2 border-red-200 p-6">
                <h3 class="text-lg font-semibold text-red-900 flex items-center mb-4">
                    <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    &nbsp;Danger Zone
                </h3>
                <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                    <p class="text-sm text-red-800 mb-4">
                        <strong>Warning:</strong> Deleting this site will permanently remove all associated files, databases, and configurations. This action cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('sites.destroy', $site) }}"
                          onsubmit="return confirm('Are you absolutely sure you want to delete this site? This action cannot be undone and will remove all files, databases, and configurations.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Site Permanently
                        </button>
                    </form>
                </div>
            </div>
            @endrole
        </div>
    </div>
    <script>
        function logUpdater(siteId, status) {
            return {
                log: @json($site->getLog()),
                init() {
                    if(status !== 'active') {
                        this.fetchLogs();
                        setInterval(() => this.fetchLogs(), 3000);
                    }
                },
                async fetchLogs() {
                    try {
                        const res = await fetch(`/sites/${siteId}/logs`);
                        if (!res.ok) throw new Error('Network error');
                        const data = await res.json();
                        this.log = data.log;
                        if (data.status === 'active') {
                            setTimeout(function () {
                                location.reload();
                            }, 2000)
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            }
        }

        async function renewSSL(siteId) {
            const btn = document.getElementById('renewSSLBtn');
            const originalContent = btn.innerHTML;

            // Disable button and show loading state
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Renewing...
            `;
            btn.classList.remove('hover:bg-blue-700');
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                const response = await fetch(`/sites/${siteId}/renew-ssl`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Show success message
                    showNotification('SSL certificate renewed successfully!', 'success');
                    btn.innerHTML = `
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Renewed!
                    `;

                    // Reset button after 3 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Failed to renew SSL certificate');
                }
            } catch (error) {
                console.error('Error renewing SSL:', error);
                showNotification(error.message || 'Failed to renew SSL certificate', 'error');

                // Reset button
                btn.innerHTML = originalContent;
                btn.disabled = false;
                btn.classList.add('hover:bg-blue-700');
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        function openBranchEditor() {
            document.getElementById('branchViewRow').classList.add('hidden');
            document.getElementById('branchEditRow').classList.remove('hidden');
            document.getElementById('branchEditRow').classList.add('flex');
            document.getElementById('newBranchInput').focus();
        }

        function closeBranchEditor() {
            document.getElementById('branchEditRow').classList.add('hidden');
            document.getElementById('branchEditRow').classList.remove('flex');
            document.getElementById('branchViewRow').classList.remove('hidden');
            document.getElementById('branchChangeHint').classList.add('hidden');
        }

        async function submitBranchChange(siteId) {
            const input = document.getElementById('newBranchInput');
            const branch = input.value.trim();
            const btn = document.getElementById('confirmBranchBtn');
            const hint = document.getElementById('branchChangeHint');

            if (!branch) {
                input.focus();
                return;
            }

            if (!confirm(`Switch to branch "${branch}"? This fetches it and redeploys the live site now.`)) {
                return;
            }

            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Deploying...';
            hint.classList.add('hidden');

            try {
                const response = await fetch(`/sites/${siteId}/change-branch`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ branch })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Failed to switch branch');
                }
            } catch (error) {
                showNotification(error.message || 'Failed to switch branch', 'error');
                hint.textContent = error.message;
                hint.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        async function showDeployKey(domain, serverId) {
            const btn = document.getElementById('deployKeyBtn');
            const box = document.getElementById('deployKeyBox');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = 'Fetching...';

            try {
                const response = await fetch('{{ route("ssh-key.get") }}?' + new URLSearchParams({ domain: domain, hestia_server_id: serverId }), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    document.getElementById('deployKeyText').value = data.public_key;
                    box.classList.remove('hidden');
                } else {
                    throw new Error(data.message || 'Failed to fetch deploy key');
                }
            } catch (error) {
                showNotification(error.message || 'Failed to fetch deploy key', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        async function generateToken(siteId) {
            const btn = document.getElementById('generate-token');
            const originalContent = btn.innerHTML;

            // Disable button and show loading state
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Generating...
            `;
            btn.classList.remove('hover:bg-blue-700');
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                const response = await fetch(`/sites/${siteId}/generate-token`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showNotification('Token Generated successfully!', 'success');
                    btn.innerHTML = `
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Generated! reloading...
                    `;

                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Failed to generate token!');
                }
            } catch (error) {
                console.error('Error renewing SSL:', error);
                showNotification(error.message || 'Failed to generate token!', 'error');

                // Reset button
                btn.innerHTML = originalContent;
                btn.disabled = false;
                btn.classList.add('hover:bg-blue-700');
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white font-medium`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Fade out and remove after 5 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        function copyToClipboard(text, buttonElement) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);

            const originalContent = buttonElement.innerHTML;
            buttonElement.classList.remove('text-gray-400', 'hover:text-gray-600');
            buttonElement.classList.add('text-green-600');
            buttonElement.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

            setTimeout(() => {
                buttonElement.innerHTML = originalContent;
                buttonElement.classList.add('text-gray-400', 'hover:text-gray-600');
                buttonElement.classList.remove('text-green-600');
            }, 2000);
        }

        async function retryProvisioning(siteId) {
            const btn = document.getElementById('retryBtn');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Cleaning up & retrying...
            `;
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                const response = await fetch(`/sites/${siteId}/retry`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Failed to retry provisioning');
                }
            } catch (error) {
                console.error('Error retrying provisioning:', error);
                showNotification(error.message || 'Failed to retry provisioning', 'error');

                btn.innerHTML = originalContent;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        async function changeStatus(siteId, currentStatus) {
            const btn = document.getElementById('changeStatusBtn');
            const originalContent = btn.innerHTML;

            // Disable button and show loading state
            btn.disabled = true;
            const actionText = currentStatus === 'active' ? 'Suspending' : 'Unsuspending';
            btn.innerHTML = `
                <svg class="animate-spin h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                ${actionText}...
            `;
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                const response = await fetch(`/sites/${siteId}/change-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showNotification(data.message, 'success');

                    // Reload page after 2 seconds to reflect new status
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to change status');
                }
            } catch (error) {
                console.error('Error changing status:', error);
                showNotification(error.message || 'Failed to change status', 'error');

                // Reset button
                btn.innerHTML = originalContent;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        }

        function serverLogViewer(siteId) {
            return {
                type: 'access',
                lines: 100,
                content: '',
                loading: false,
                async fetchLog() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({ type: this.type, lines: this.lines });
                        const response = await fetch(`/sites/${siteId}/server-log?${params}`);
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to fetch log');
                        }
                        this.content = data.content || '(empty)';
                    } catch (error) {
                        showNotification(error.message || 'Failed to fetch log', 'error');
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

        function backupManager(siteId) {
            return {
                backups: [],
                requesting: { database: false, images: false },
                pollTimer: null,
                init() {
                    this.fetchBackups();
                },
                formatSize(bytes) {
                    if (!bytes) return '—';
                    const units = ['B', 'KB', 'MB', 'GB'];
                    let i = 0;
                    while (bytes >= 1024 && i < units.length - 1) {
                        bytes /= 1024;
                        i++;
                    }
                    return bytes.toFixed(1) + ' ' + units[i];
                },
                async fetchBackups() {
                    try {
                        const res = await fetch(`/sites/${siteId}/backups`);
                        const data = await res.json();
                        this.backups = data.backups;

                        const hasPending = this.backups.some(b => b.status === 'pending');
                        clearTimeout(this.pollTimer);
                        if (hasPending) {
                            this.pollTimer = setTimeout(() => this.fetchBackups(), 5000);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },
                async requestBackup(type) {
                    this.requesting[type] = true;
                    try {
                        const response = await fetch(`/sites/${siteId}/backups/${type}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || `Failed to queue ${type} backup`);
                        }
                        showNotification(data.message, 'success');
                        this.fetchBackups();
                    } catch (error) {
                        showNotification(error.message || `Failed to queue ${type} backup`, 'error');
                    } finally {
                        this.requesting[type] = false;
                    }
                },
                async deleteBackup(backupId) {
                    if (!confirm('Delete this backup?')) return;
                    try {
                        const response = await fetch(`/sites/${siteId}/backups/${backupId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'Failed to delete backup');
                        }
                        this.fetchBackups();
                    } catch (error) {
                        showNotification(error.message || 'Failed to delete backup', 'error');
                    }
                }
            }
        }
    </script>
</x-app-layout>
