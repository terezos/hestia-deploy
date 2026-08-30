<?php

namespace App\Http\Controllers;

use App\Jobs\DatabaseBackupJob;
use App\Jobs\ImagesBackupJob;
use App\Models\HestiaServer;
use App\Models\Site;
use App\Models\SiteBackup;
use App\Services\ProvisioningService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $query = Site::query()->with('hestiaServer');

        // Filter by domain search
        if ($request->filled('search')) {
            $query->where('domain', 'like', '%' . $request->search . '%');
        }

        // Filter by framework
        if ($request->filled('framework')) {
            $query->where('framework', $request->framework);
        }

        // Filter by server
        if ($request->filled('hestia_server_id')) {
            $query->where('hestia_server_id', $request->hestia_server_id);
        }

        $sites = $query->latest()->paginate(20);
        $servers = HestiaServer::orderBy('name')->get();

        return view('sites.index', compact('sites', 'servers'));
    }

    public function create()
    {
        $servers = HestiaServer::where('is_active', true)->orderBy('name')->get();

        return view('sites.create', compact('servers'));
    }

    public function attachableUsers(Request $request)
    {
        $validated = $request->validate([
            'hestia_server_id' => 'required|exists:hestia_servers,id',
        ]);

        $server = HestiaServer::findOrFail($validated['hestia_server_id']);

        try {
            $users = app(ProvisioningService::class)->listUsers($server);

            return response()->json(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function listBranches(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string',
            'hestia_server_id' => 'required|exists:hestia_servers,id',
            'repo_url' => 'required|string',
        ]);

        $server = HestiaServer::findOrFail($validated['hestia_server_id']);

        try {
            $branches = app(ProvisioningService::class)->listRemoteBranches(
                $server,
                $validated['domain'],
                $validated['repo_url']
            );

            return response()->json(['success' => true, 'branches' => $branches]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function changeBranch(Request $request, Site $site)
    {
        $validated = $request->validate([
            'branch' => 'required|string|max:255',
        ]);

        if ($validated['branch'] === $site->branch) {
            return response()->json([
                'success' => false,
                'message' => 'Site is already on this branch.',
            ], 422);
        }

        try {
            app(ProvisioningService::class)->switchBranch($site, $validated['branch']);

            return response()->json([
                'success' => true,
                'message' => "Branch switched to \"{$site->branch}\" and redeployed!",
                'branch' => $site->branch,
            ]);
        } catch (Exception $e) {
            $site->addLog("❌ Branch switch failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|unique:sites',
            'hestia_server_id' => 'required|exists:hestia_servers,id',
            'attach_to_hestia_username' => 'nullable|string',
            'repo_url' => 'required|string',
            'branch' => 'required|string',
            'php_version' => 'required|in:8.2,8.3,7.4,8.4',
            'ssl_enabled' => 'boolean',
            'overwrite_suffix' => 'nullable|string',
            'framework' => 'required|in:opencart_octopus,opencart_default,wordpress,laravel',
            'run_composer' => 'boolean',
            'database_file' => 'nullable|file|mimes:zip|max:512000',
        ]);

        // Auto-generate database credentials. Kept short (not domain-derived) because
        // HestiaCP prefixes both with "{hestia_username}_" before creating the actual
        // MySQL account, and MySQL usernames are capped at 32 characters total.
        $dbToken = $this->generateDbToken();

        $validated['database_name'] = 'db_' . $dbToken;
        $validated['database_user'] = 'u_' . $dbToken;
        $validated['database_password'] = Str::random(16);
        $validated['ssl_enabled'] = $request->has('ssl_enabled');
        $validated['run_composer'] = $request->has('run_composer');
        $validated['webhook_token'] = Str::random(32);

        if (!empty($validated['attach_to_hestia_username'])) {
            $attachTo = Site::where('hestia_username', $validated['attach_to_hestia_username'])
                ->where('hestia_server_id', $validated['hestia_server_id'])
                ->first();

            $validated['hestia_username'] = $validated['attach_to_hestia_username'];
            $validated['hestia_user_password'] = $attachTo?->hestia_user_password;
        } else {
            $validated['hestia_username'] = $this->generateHestiaUsername($validated['domain']);
            $validated['hestia_user_password'] = Str::random(16);
        }

        unset($validated['attach_to_hestia_username']);

        // Handle database file upload
        if ($request->hasFile('database_file')) {
            $file = $request->file('database_file');
            $filename = $validated['domain'] . '_' . time() . '.zip';
            $path = $file->storeAs('database-imports', $filename, 'local');
            $validated['database_file_path'] = storage_path('app/' . $path);
        }

        $site = Site::create($validated);

        dispatch(/**
         * @throws Exception
         */ function () use ($site) {
            app(ProvisioningService::class)->provision($site);
        })->afterResponse();

        return redirect()->route('sites.show', $site)
            ->with('success', 'Site provisioning started!');
    }

    /**
     * Derive a HestiaCP-safe username (lowercase letters/digits/underscore, <=20 chars,
     * must start with a letter) for the dedicated per-domain user, ensuring uniqueness.
     */
    protected function generateHestiaUsername(string $domain): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $domain));
        $base = substr($base, 0, 12) ?: 'site';

        if (!preg_match('/^[a-z]/', $base)) {
            $base = 'u' . $base;
        }

        do {
            $username = substr($base, 0, 14) . '_' . Str::lower(Str::random(4));
        } while (Site::where('hestia_username', $username)->exists());

        return $username;
    }

    /**
     * Short random token for database_name/database_user. Deliberately not
     * domain-derived: HestiaCP prefixes both with "{hestia_username}_" (up to 21
     * chars) before creating the real MySQL account, and MySQL usernames cap at 32.
     */
    protected function generateDbToken(): string
    {
        do {
            $token = Str::lower(Str::random(6));
        } while (Site::where('database_user', 'u_' . $token)->exists());

        return $token;
    }

    public function show(Site $site)
    {
        return view('sites.show', compact('site'));
    }

    public function getLogs(Site $site)
    {
        return response()->json([
            'status' => $site->status,
            'log' => $site->getLog(),
            'updated_at' => $site->updated_at->toISOString()
        ]);
    }

    public function destroy(Site $site)
    {
        try {
            app(ProvisioningService::class)->cleanup($site);
        } catch (Exception $e) {
            logger()->warning("Cleanup failed while deleting site {$site->domain}: " . $e->getMessage());
        }

        $site->delete();

        return redirect()->route('sites.index')
            ->with('success', 'Site deleted!');
    }

    public function retry(Site $site)
    {
        if ($site->status !== 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed sites can be retried.',
            ], 422);
        }

        $site->addLog("🔁 Retry requested, cleaning up previous attempt...");

        try {
            app(ProvisioningService::class)->cleanup($site);
        } catch (Exception $e) {
            $site->addLog("⚠️ Cleanup warning: " . $e->getMessage());
        }

        $site->update(['status' => 'pending']);

        dispatch(function () use ($site) {
            app(ProvisioningService::class)->provision($site);
        })->afterResponse();

        return response()->json([
            'success' => true,
            'message' => 'Retry started!',
        ]);
    }

    public function getSSHKey(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string',
            'hestia_server_id' => 'required|exists:hestia_servers,id',
        ]);

        $server = HestiaServer::findOrFail($validated['hestia_server_id']);

        try {
            $deployKey = app(ProvisioningService::class)->getOrCreateDeployKey($server, $validated['domain']);

            if (empty($deployKey['public_key'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'SSH key file is empty or does not exist'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'public_key' => $deployKey['public_key'],
                'key_path' => $deployKey['key_path'],
                'domain' => $validated['domain'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateToken(Site $site)
    {
        $site->webhook_token = Str::random(32);
        $site->save();
        return response()->json([
            'success' => true,
            'message' => 'New webhook token generated',
            'webhook_token' => $site->webhook_token
        ]);
    }

    public function changeStatus(Site $site)
    {
        $suspending = $site->status !== 'suspended';
        $action = $suspending ? 'Suspending' : 'Unsuspending';
        $status = $suspending ? 'suspended' : 'active';

        $site->addLog("$action domain...");

        try {
            app(ProvisioningService::class)->setDomainSuspended($site, $suspending);

            $site->status = $status;
            $site->addLog("$action domain successfully");
            $site->save();

            return response()->json([
                'success' => true,
                'message' => "$action domain successfully",
            ]);

        } catch (Exception $e) {
            $site->addLog("$action domain failed: " . $e->getMessage());
            $site->save();
            return response()->json([
                'success' => false,
                'message' => "Failed on $action domain: " . trim($e->getMessage())
            ], 500);
        }
    }

    public function renewSSL(Site $site)
    {
        $site->addLog("SSL renewal initiated...");

        try {
            app(ProvisioningService::class)->renewSSLCertificate($site);

            $site->addLog("SSL certificate renewed successfully");
            $site->save();
            return response()->json([
                'success' => true,
                'message' => 'SSL certificate renewed successfully',
            ]);

        } catch (Exception $e) {
            $site->addLog("SSL renewal failed: " . $e->getMessage());
            $site->save();
            return response()->json([
                'success' => false,
                'message' => 'Failed to renew SSL certificate: ' . trim($e->getMessage())
            ], 500);
        }
    }

    public function backupDatabase(Site $site)
    {
        $backup = $site->backups()->create(['type' => 'database', 'status' => 'pending']);

        DatabaseBackupJob::dispatch($site->id, $backup->id);

        $site->addLog("💾 Database backup queued...");

        return response()->json(['success' => true, 'message' => 'Database backup queued', 'backup' => $backup]);
    }

    public function backupImages(Site $site)
    {
        if (!ProvisioningService::imagesFolderForFramework($site->framework)) {
            return response()->json([
                'success' => false,
                'message' => "No images folder mapping for framework: {$site->framework}"
            ], 422);
        }

        $backup = $site->backups()->create(['type' => 'images', 'status' => 'pending']);

        ImagesBackupJob::dispatch($site->id, $backup->id);

        $site->addLog("🖼️ Images backup queued...");

        return response()->json(['success' => true, 'message' => 'Images backup queued', 'backup' => $backup]);
    }

    public function backups(Site $site)
    {
        return response()->json([
            'backups' => $site->backups()->latest()->get(),
        ]);
    }

    public function downloadBackup(Site $site, SiteBackup $backup)
    {
        if ($backup->site_id !== $site->id) {
            abort(404);
        }

        if ($backup->status !== 'completed' || !$backup->path || !Storage::disk('local')->exists($backup->path)) {
            return response()->json(['success' => false, 'message' => 'Backup file not available'], 404);
        }

        return Storage::disk('local')->download($backup->path, $backup->filename);
    }

    public function destroyBackup(Site $site, SiteBackup $backup)
    {
        if ($backup->site_id !== $site->id) {
            abort(404);
        }

        $backup->delete();

        return response()->json(['success' => true, 'message' => 'Backup deleted']);
    }

    public function serverLog(Request $request, Site $site)
    {
        $validated = $request->validate([
            'type' => 'required|in:access,error',
            'lines' => 'nullable|integer|min:1|max:5000',
        ]);

        $lines = $validated['lines'] ?? 100;
        $filename = $validated['type'] === 'error'
            ? "{$site->domain}.error.log"
            : "{$site->domain}.log";

        $path = "/home/{$site->hestia_username}/web/{$site->domain}/logs/{$filename}";

        try {
            $output = app(ProvisioningService::class)->sshExec(
                $site,
                sprintf('tail -n %d %s 2>&1', $lines, escapeshellarg($path))
            );

            return response()->json(['success' => true, 'content' => $output]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deployWebhook(Request $request, Site $site, string $token)
    {
        // Both comparisons use hash_equals: this endpoint is unauthenticated and
        // the token is the only thing standing between a stranger and a deploy.
        $tokenValid = is_string($site->webhook_token)
            && hash_equals($site->webhook_token, $token);

        $headerValid = hash_equals(
            (string) config('hestia.webhook_header_token'),
            (string) $request->header('X-Gitlab-Token')
        );

        if (! $tokenValid || ! $headerValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook token or unauthorized source'
            ], 403);
        }

        $site->addLog("Webhook deployment initiated...");

        try {
            $provisioning = app(ProvisioningService::class);

            $bareRepo = "/home/{$site->hestia_username}/git/{$site->domain}.git";
            $webRoot  = "/home/{$site->hestia_username}/web/{$site->domain}/public_html";

            $safeKeyName = str_replace(['.', '-', ' '], '_', $site->domain);
            $sshKeyPath  = $provisioning->sshHomeDir($site) . "/.ssh/id_rsa_{$safeKeyName}";

            $deployCommand = sprintf(
                'GIT_SSH_COMMAND="ssh -i %s -o IdentitiesOnly=yes -o StrictHostKeyChecking=no" git --git-dir=%s --work-tree=%s fetch origin 2>&1 && git --git-dir=%s --work-tree=%s reset --hard origin/%s 2>&1; echo "EXIT:$?"',
                escapeshellarg($sshKeyPath),
                escapeshellarg($bareRepo),
                escapeshellarg($webRoot),
                escapeshellarg($bareRepo),
                escapeshellarg($webRoot),
                escapeshellarg($site->branch)
            );

            $site->addLog("Executing fetch + reset...");
            $output = $provisioning->sshExec($site, $deployCommand, 0);
            $site->addLog("Deploy output: " . trim($output));

            if (preg_match('/EXIT:(\d+)/', $output, $m) && $m[1] === '0') {
                $site->addLog("Webhook deployment completed successfully");
                $site->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Deployment successful',
                    'output' => trim($output)
                ]);
            } else {
                $site->addLog("Webhook deployment failed: " . trim($output));
                $site->save();
                return response()->json([
                    'success' => false,
                    'message' => 'Deploy failed',
                    'output' => trim($output)
                ], 500);
            }

        } catch (\Exception $e) {
            $site->addLog("Webhook deployment failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
