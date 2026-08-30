@php
    $server = $server ?? null;
    $isEdit = $server !== null;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Server Identity</h3>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Server Name *</label>
        <input type="text" name="name" value="{{ old('name', $server->name ?? '') }}"
               placeholder="EU West 1"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
               required>
        @error('name')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">Panel URL *</label>
        <input type="text" name="panel_url" value="{{ old('panel_url', $server->panel_url ?? '') }}"
               placeholder="https://hestia.example.com:8083"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
               required>
        @error('panel_url')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Panel API Auth --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Panel API Authentication</h3>
    <p class="text-xs text-gray-500 mb-4">HestiaCP access-key/secret-key token, sent as a single <code>hash</code> parameter on every API call.</p>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Access Key {{ $isEdit ? '' : '*' }}</label>
        <input type="text" name="access_key" value="{{ old('access_key', $server->access_key ?? '') }}"
               placeholder="{{ $isEdit ? 'Leave blank to keep current key' : '' }}"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
        @error('access_key')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
    <div class="mb-2">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Secret Key {{ $isEdit ? '' : '*' }}</label>
        <input type="password" name="secret_key"
               placeholder="{{ $isEdit ? 'Leave blank to keep current secret' : '' }}"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
        @error('secret_key')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Root SSH Access --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Root SSH Access</h3>
    <p class="text-xs text-gray-500 mb-4">Used for git deploys and file/permission fixes. Separate from the panel API credentials above.</p>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">SSH Host *</label>
        <input type="text" name="ssh_host" value="{{ old('ssh_host', $server->ssh_host ?? '') }}"
               placeholder="hestia.example.com"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
               required>
        @error('ssh_host')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">SSH User *</label>
        <input type="text" name="ssh_user" value="{{ old('ssh_user', $server->ssh_user ?? 'root') }}"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"
               required>
        @error('ssh_user')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">SSH Password</label>
        <input type="password" name="ssh_password"
               placeholder="{{ $isEdit ? 'Leave blank to keep current password' : '' }}"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
        @error('ssh_password')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-2">SSH Private Key</label>
        <textarea name="ssh_private_key" rows="6"
                  placeholder="{{ $isEdit ? 'Leave blank to keep current key' : "-----BEGIN OPENSSH PRIVATE KEY-----\n...\n-----END OPENSSH PRIVATE KEY-----" }}"
                  class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 font-mono text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150"></textarea>
        <p class="text-xs text-gray-500 mt-1.5">Paste the private key whose matching public key is already in this account's <code>authorized_keys</code> on the server. Alternative to SSH password above — either works.</p>
        @error('ssh_private_key')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

{{-- Misc --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Other Settings</h3>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Git Token</label>
        <input type="password" name="git_token"
               placeholder="{{ $isEdit ? 'Leave blank to keep current token' : '' }}"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
        <p class="text-xs text-gray-500 mt-1.5">Read access token used for Composer private-package auth. Provider-agnostic — each site picks whether it is a GitLab, GitHub or Bitbucket token.</p>
        @error('git_token')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Default HestiaCP Package</label>
        <input type="text" name="default_package" value="{{ old('default_package', $server->default_package ?? 'default') }}"
               class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150">
        <p class="text-xs text-gray-500 mt-1.5">Used as the package template when creating a per-domain HestiaCP user.</p>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="is_active" value="1" id="is_active"
               class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            {{ old('is_active', $server->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active" class="ml-2 text-sm font-semibold text-gray-700">Active (selectable when creating new sites)</label>
    </div>
</div>
