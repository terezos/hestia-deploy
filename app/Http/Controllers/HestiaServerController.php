<?php

namespace App\Http\Controllers;

use App\Models\HestiaServer;
use Illuminate\Http\Request;

class HestiaServerController extends Controller
{
    public function index()
    {
        $servers = HestiaServer::withCount('sites')->latest()->get();

        return view('servers.index', compact('servers'));
    }

    public function create()
    {
        return view('servers.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateServer($request);

        HestiaServer::create($validated);

        return redirect()->route('servers.index')->with('success', 'Server added!');
    }

    public function edit(HestiaServer $server)
    {
        return view('servers.edit', compact('server'));
    }

    public function update(Request $request, HestiaServer $server)
    {
        $validated = $this->validateServer($request, $server);

        // Blank secret fields on update mean "keep the existing value".
        foreach (['access_key', 'secret_key', 'ssh_password', 'ssh_private_key', 'gitlab_token'] as $secretField) {
            if (empty($validated[$secretField])) {
                unset($validated[$secretField]);
            }
        }

        $server->update($validated);

        return redirect()->route('servers.index')->with('success', 'Server updated!');
    }

    public function destroy(HestiaServer $server)
    {
        if ($server->sites()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a server that still has sites on it.',
            ], 422);
        }

        $server->delete();

        return response()->json([
            'success' => true,
            'message' => 'Server deleted successfully',
        ]);
    }

    protected function validateServer(Request $request, ?HestiaServer $server = null): array
    {
        $isUpdate = $server !== null;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'panel_url' => 'required|url',
            'access_key' => ($isUpdate ? 'nullable' : 'required') . '|string',
            'secret_key' => ($isUpdate ? 'nullable' : 'required') . '|string',
            'ssh_host' => 'required|string|max:255',
            'ssh_user' => 'required|string|max:255',
            'ssh_password' => 'nullable|string',
            'ssh_private_key' => 'nullable|string',
            'gitlab_token' => 'nullable|string',
            'default_package' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['default_package'] = $validated['default_package'] ?? 'default';
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
