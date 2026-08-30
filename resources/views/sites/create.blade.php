<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Deploy New Site</h2>
                <p class="mt-1 text-sm text-gray-600">Configure and deploy your new site to the cloud</p>
            </div>
            <a href="{{ route('sites.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Sites
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sites.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- Site Configuration Section --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            Site Configuration
                        </h3>

                        {{-- Domain Name --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Domain Name *</label>
                            <input type="text" name="domain" id="domain" value="{{ old('domain') }}"
                                   placeholder="example.com"
                                   class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                   required>
                            <p class="text-xs text-gray-500 mt-1.5">Enter the full domain name for your site</p>
                            @error('domain')
                            <p class="text-red-600 text-sm mt-1 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- HestiaCP Server --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">HestiaCP Server *</label>
                            <select name="hestia_server_id" id="hestia_server_id"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                    required>
                                <option value="">Select a server...</option>
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}" {{ (string) old('hestia_server_id') === (string) $server->id ? 'selected' : '' }}>
                                        {{ $server->name }} ({{ $server->ssh_host }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1.5">Which server this site gets provisioned on</p>
                            @error('hestia_server_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Attach to existing HestiaCP user (subdomains) --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Attach to Existing User (optional)</label>
                            <select name="attach_to_hestia_username" id="attach_to_hestia_username"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                                <option value="">Create a new isolated user</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1.5">Pick an existing HestiaCP user on this server to host this domain under it (e.g. a subdomain of that user) instead of creating a new one.</p>
                            @error('attach_to_hestia_username')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Framework --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Framework *</label>
                            <select name="framework" id="framework"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                    onchange="document.getElementById('run_composer_wrap').classList.toggle('hidden', !['opencart_octopus','opencart_default','laravel'].includes(this.value))"
                                    required>
                                <option value="opencart_octopus" {{ old('framework') === 'opencart_octopus' ? 'selected' : '' }}>Opencart - Octopus</option>
                                <option value="opencart_default" {{ old('framework') === 'opencart_default' ? 'selected' : '' }}>Opencart - Default</option>
                                <option value="wordpress" {{ old('framework') === 'wordpress' ? 'selected' : '' }}>WordPress</option>
                                <option value="laravel" {{ old('framework') === 'laravel' ? 'selected' : '' }}>Laravel</option>
                            </select>
                            @error('framework')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Run Composer Checkbox (opencart/laravel only) --}}
                        <div id="run_composer_wrap"
                             class="flex items-start p-4 mb-6 bg-blue-50 rounded-lg border border-blue-200 {{ in_array(old('framework'), ['opencart_octopus', 'opencart_default', 'laravel']) ? '' : 'hidden' }}">
                            <input type="checkbox" name="run_composer" value="1" id="run_composer"
                                   class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ old('run_composer', true) ? 'checked' : '' }}>
                            <label for="run_composer" class="ml-3">
                                <span class="block text-sm font-semibold text-gray-900">Run Composer Install</span>
                                <span class="block text-xs text-gray-600 mt-0.5">Run "composer install" after deploying code (Opencart / Laravel only)</span>
                            </label>
                        </div>

                        {{-- PHP Version --}}
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PHP Version *</label>
                            <select name="php_version"
                                    class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                                    required>
                                <option value="7.4" {{ old('php_version') === '7.4' ? 'selected' : '' }}>PHP 7.4</option>
                                <option value="8.2" {{ old('php_version', '8.2') === '8.2' ? 'selected' : '' }}>PHP 8.2</option>
                                <option value="8.3" {{ old('php_version') === '8.3' ? 'selected' : '' }}>PHP 8.3</option>
                                <option value="8.4" {{ old('php_version') === '8.4' ? 'selected' : '' }}>PHP 8.4</option>
                            </select>
                            @error('php_version')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- SSL Checkbox --}}
                        <div class="flex items-start p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <input type="checkbox" name="ssl_enabled" value="1" id="ssl_enabled"
                                   class="mt-1 h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                {{ old('ssl_enabled', true) ? 'checked' : '' }}>
                            <label for="ssl_enabled" class="ml-3">
                                <span class="block text-sm font-semibold text-gray-900">Enable SSL Certificate</span>
                                <span class="block text-xs text-gray-600 mt-0.5">Automatically provision Let's Encrypt SSL certificate (DNS must point to server)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Repository Configuration Section --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Repository Configuration
                    </h3>

                    {{-- Git Repository --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-700">Git Repository URL *</label>
                            <button type="button" onclick="showSSHKey()"
                                    class="inline-flex items-center px-3 py-1.5 bg-orange-100 hover:bg-orange-200 text-orange-700 text-xs font-semibold rounded-lg transition duration-150">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Get Deploy SSH Key
                            </button>
                        </div>
                        <input type="text" name="repo_url" value="{{ old('repo_url') }}"
                               placeholder="git@gitlab.com:username/repository.git or git@github.com:username/repository.git"
                               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                               required>
                        <p class="text-xs text-gray-500 mt-1.5">SSH or HTTPS URL of your Git repository</p>
                        @error('repo_url')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Git Provider --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Git Provider</label>
                        <select name="git_provider"
                                class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                            <option value="">Auto-detect from repository URL</option>
                            @foreach (['gitlab' => 'GitLab', 'github' => 'GitHub', 'bitbucket' => 'Bitbucket'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('git_provider') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1.5">Only decides how the server's Git token is written into <code>auth.json</code> for Composer. Deploys themselves use the per-domain SSH key and work with any provider.</p>
                        @error('git_provider')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Git Branch --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-700">Git Branch *</label>
                            <button type="button" onclick="fetchBranches()" id="fetchBranchesBtn"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-semibold rounded-lg transition duration-150">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Fetch Branches
                            </button>
                        </div>
                        <input type="text" name="branch" id="branch" value="{{ old('branch', 'developer') }}"
                               list="branchesList" autocomplete="off"
                               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
                               required>
                        <datalist id="branchesList"></datalist>
                        <p class="text-xs text-gray-500 mt-1.5" id="branchHint">Branch to deploy from your repository — add the SSH deploy key to your repository first, then click "Fetch Branches" to list what's available</p>
                        @error('branch')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Overwrite Suffix --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">File Overwrite Suffix (OpenCart)</label>
                        <input type="text" name="overwrite_suffix" value="{{ old('overwrite_suffix') }}"
                               placeholder="demo"
                               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
                        <p class="text-xs text-gray-500 mt-1.5">Custom file suffix for OpenCart configuration overrides</p>
                        @error('overwrite_suffix')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Database Section --}}
{{--                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">--}}
{{--                    <h3 class="text-lg font-semibold text-gray-900 flex items-center mb-4">--}}
{{--                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">--}}
{{--                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>--}}
{{--                        </svg>--}}
{{--                        Database Import (Optional)--}}
{{--                    </h3>--}}

{{--                    --}}{{-- Database File Upload --}}
{{--                    <div>--}}
{{--                        <label class="block text-sm font-semibold text-gray-700 mb-2">Database ZIP File</label>--}}
{{--                        <div class="flex items-center justify-center w-full">--}}
{{--                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition duration-150">--}}
{{--                                <div class="flex flex-col items-center justify-center pt-5 pb-6">--}}
{{--                                    <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 85px">--}}
{{--                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>--}}
{{--                                    </svg>--}}
{{--                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>--}}
{{--                                    <p class="text-xs text-gray-500">ZIP file containing .sql database</p>--}}
{{--                                </div>--}}
{{--                                <input type="file" name="database_file" accept=".zip" class="hidden">--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                        <p class="text-xs text-gray-500 mt-2">Upload a database backup to automatically import during deployment</p>--}}
{{--                        @error('database_file')--}}
{{--                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>--}}
{{--                        @enderror--}}
{{--                    </div>--}}
{{--                </div>--}}

                {{-- Auto-generated Info --}}
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200 p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-blue-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Auto-Generated Resources</h4>
                            <p class="text-xs text-gray-600 mb-3">The following will be automatically created during deployment:</p>
                            <ul class="space-y-2 text-sm text-gray-700">
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Database: <code class="ml-1 font-mono bg-white px-2 py-0.5 rounded text-xs">eight_{domain}_db</code>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Database User: <code class="ml-1 font-mono bg-white px-2 py-0.5 rounded text-xs">eight_{domain}_user</code>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Database Password: <span class="ml-1 text-gray-600 text-xs">(secure 16-character random password)</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Isolated HestiaCP User: <span class="ml-1 text-gray-600 text-xs">(dedicated account owns this domain's web dir + database)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('sites.index') }}" class="inline-flex items-center px-4 py-2 bg-yellow-400 hover:bg-gray-800 text-white font-semibold rounded-lg shadow-lg transition duration-150 ease-in-out transform hover:scale-155">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-black hover:bg-gray-800 text-white font-semibold rounded-lg shadow-lg transition duration-150 ease-in-out transform hover:scale-155">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Deploy Site
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- SSH Key Modal --}}
    <div id="sshKeyModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl  mx-4 max-h-[80vh] overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Server SSH Public Key
                    </h3>
                    <button onclick="closeSSHKeyModal()" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div id="sshKeyLoading" class="text-center py-8">
                    <svg class="animate-spin h-10 w-10 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24" style="width: 20px">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-4 text-gray-600">Generating SSH key...</p>
                </div>

                <div id="sshKeyContent" class="hidden">
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-orange-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-orange-800">
                                <p class="font-semibold mb-1">Add this SSH key to your Git repository</p>
                                <p>Copy the key below and add it to your repository's deploy keys (GitLab: Settings → Repository → Deploy Keys; GitHub: Settings → Deploy keys; Bitbucket: Repository settings → Access keys):</p>
                                <ol class="list-decimal ml-4 mt-2 space-y-1">
                                    <li>Go to your Git repository</li>
                                    <li>Navigate to Settings → Repository → Deploy Keys</li>
                                    <li>Paste the key and give it write access if needed</li>
                                    <li>Save the deploy key</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">SSH Public Key</label>
                        <div class="relative">
                            <textarea id="sshKeyText" readonly
                                      class="w-full h-32 border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-xs bg-gray-50 resize-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"></textarea>
                            <button onclick="copySSHKey()"
                                    class="absolute top-2 right-2 inline-flex items-center px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold rounded-lg transition duration-150">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy Key
                            </button>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-800">
                                <p class="font-semibold mb-1">Key Location on Server</p>
                                <code id="keyPathDisplay" class="text-xs bg-white px-2 py-1 rounded break-all">/home/eight/.ssh/id_rsa_{domain}.pub</code>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sshKeyError" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-red-800">
                                <p class="font-semibold mb-1">Error</p>
                                <p id="sshKeyErrorMessage">Failed to retrieve SSH key. Please try again.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                <button onclick="closeSSHKeyModal()"
                        class="w-full inline-flex items-center justify-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition duration-150">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        async function refreshAttachableUsers() {
            const serverId = document.getElementById('hestia_server_id').value;
            const select = document.getElementById('attach_to_hestia_username');
            const previousValue = select.value;

            select.innerHTML = '<option value="">Create a new isolated user</option>';

            if (!serverId) {
                return;
            }

            select.disabled = true;
            const loadingOption = new Option('Loading users…', '', true, true);
            select.appendChild(loadingOption);

            try {
                const response = await fetch('{{ route("sites.attachable-users") }}?' + new URLSearchParams({ hestia_server_id: serverId }), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();

                loadingOption.remove();

                if (data.success) {
                    data.users.forEach(username => {
                        const option = document.createElement('option');
                        option.value = username;
                        option.textContent = username;
                        select.appendChild(option);
                    });
                } else {
                    select.appendChild(new Option('Failed to load users — ' + data.message, '', false, false));
                }
            } catch (e) {
                loadingOption.remove();
                select.appendChild(new Option('Failed to load users from server', '', false, false));
            } finally {
                select.disabled = false;
            }

            if ([...select.options].some(o => o.value === previousValue)) {
                select.value = previousValue;
            }
        }

        document.getElementById('hestia_server_id').addEventListener('change', refreshAttachableUsers);
        refreshAttachableUsers();

        function showSSHKey() {
            const modal = document.getElementById('sshKeyModal');
            const loading = document.getElementById('sshKeyLoading');
            const content = document.getElementById('sshKeyContent');
            const error = document.getElementById('sshKeyError');

            // Get domain + server values
            const domainInput = document.querySelector('input[name="domain"]');
            const domain = domainInput.value.trim();
            const serverId = document.getElementById('hestia_server_id').value;

            // Validate domain and server are filled
            if (!domain) {
                alert('Please enter a domain name first.');
                domainInput.focus();
                return;
            }
            if (!serverId) {
                alert('Please select a HestiaCP server first.');
                document.getElementById('hestia_server_id').focus();
                return;
            }

            // Show modal and loading state
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            error.classList.add('hidden');

            // Fetch SSH key from server with domain + server parameters
            fetch('{{ route("ssh-key.get") }}?' + new URLSearchParams({ domain: domain, hestia_server_id: serverId }), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                loading.classList.add('hidden');
                if (data.success) {
                    document.getElementById('sshKeyText').value = data.public_key;
                    document.getElementById('keyPathDisplay').textContent = data.key_path + '.pub';
                    content.classList.remove('hidden');
                } else {
                    document.getElementById('sshKeyErrorMessage').textContent = data.message || 'Failed to retrieve SSH key.';
                    error.classList.remove('hidden');
                }
            })
            .catch(err => {
                loading.classList.add('hidden');
                document.getElementById('sshKeyErrorMessage').textContent = 'Network error. Please try again.';
                error.classList.remove('hidden');
            });
        }

        async function fetchBranches() {
            const domainInput = document.querySelector('input[name="domain"]');
            const domain = domainInput.value.trim();
            const serverId = document.getElementById('hestia_server_id').value;
            const repoUrlInput = document.querySelector('input[name="repo_url"]');
            const repoUrl = repoUrlInput.value.trim();
            const btn = document.getElementById('fetchBranchesBtn');
            const hint = document.getElementById('branchHint');

            if (!domain) {
                alert('Please enter a domain name first.');
                domainInput.focus();
                return;
            }
            if (!serverId) {
                alert('Please select a HestiaCP server first.');
                document.getElementById('hestia_server_id').focus();
                return;
            }
            if (!repoUrl) {
                alert('Please enter the Git repository URL first.');
                repoUrlInput.focus();
                return;
            }

            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Fetching...';

            try {
                const response = await fetch('{{ route("sites.branches") }}?' + new URLSearchParams({
                    domain: domain,
                    hestia_server_id: serverId,
                    repo_url: repoUrl,
                }), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to fetch branches');
                }

                const list = document.getElementById('branchesList');
                list.innerHTML = '';
                data.branches.forEach(branch => list.appendChild(new Option(branch)));

                hint.textContent = data.branches.length
                    ? `Found ${data.branches.length} branch(es) — pick from the list or type your own`
                    : 'No branches found on that repository';
            } catch (e) {
                hint.textContent = e.message || 'Failed to fetch branches. Make sure the SSH deploy key was added to the repository first.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }
        }

        function closeSSHKeyModal() {
            const modal = document.getElementById('sshKeyModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function copySSHKey() {
            const keyText = document.getElementById('sshKeyText');
            keyText.select();
            document.execCommand('copy');

            // Show feedback
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Copied!';

            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        }

        // Close modal when clicking outside
        document.getElementById('sshKeyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSSHKeyModal();
            }
        });
    </script>
</x-app-layout>
