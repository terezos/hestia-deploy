<?php

namespace App\Services;

use App\Models\HestiaServer;
use App\Models\Site;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Storage;

class ProvisioningService
{
    protected mixed $serverHost;
    protected mixed $serverUser;
    protected mixed $serverSshKey;
    protected mixed $serverPassword;
    protected mixed $gitlabToken;
    protected mixed $defaultPackage;

    protected bool $isOpencart = false;
    protected bool $isLaravel = false;

    /**
     * Load the HestiaCP server this site is provisioned on into the working properties.
     * @throws Exception
     */
    protected function loadServerConfig(Site $site): void
    {
        $server = $site->hestiaServer;

        if (!$server) {
            throw new Exception("Site {$site->domain} has no HestiaCP server assigned.");
        }

        $this->applyServerConfig($server);
    }

    protected function applyServerConfig(HestiaServer $server): void
    {
        $this->serverHost = $server->ssh_host;
        $this->serverUser = $server->ssh_user;
        $this->serverPassword = $server->ssh_password;
        $this->serverSshKey = $server->ssh_private_key;
        $this->gitlabToken = $server->gitlab_token;
        $this->defaultPackage = $server->default_package;
    }

    /**
     * Generate (if missing) and return the domain-specific deploy key used for GitLab
     * access. Works before a Site row exists (during site creation) or after (to
     * recover/view the key for an already-created site).
     * @throws Exception
     * @return array{public_key: string, key_path: string}
     */
    public function getOrCreateDeployKey(HestiaServer $server, string $domain): array
    {
        $this->applyServerConfig($server);

        $safeKeyName = str_replace(['.', '-', ' '], '_', $domain);
        $keyPath = $this->resolveSshHomeDir() . "/.ssh/id_rsa_{$safeKeyName}";

        $this->sshCommand("if [ ! -f {$keyPath} ]; then ssh-keygen -t rsa -b 4096 -f {$keyPath} -N '' -C '{$server->ssh_user}@{$domain}'; fi");

        return [
            'public_key' => trim($this->sshCommand("cat {$keyPath}.pub")),
            'key_path' => $keyPath,
        ];
    }

    /**
     * List remote branch names for a repo via `git ls-remote`, authenticated with the
     * domain's deploy key. Works before a Site row exists (create-form branch picker)
     * since it only needs the domain + server to locate/generate that key.
     * @throws Exception
     */
    public function listRemoteBranches(HestiaServer $server, string $domain, string $repoUrl): array
    {
        $deployKey = $this->getOrCreateDeployKey($server, $domain);

        $output = $this->sshCommand(sprintf(
            "GIT_SSH_COMMAND='ssh -i %s -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new' git ls-remote --heads %s",
            escapeshellarg($deployKey['key_path']),
            escapeshellarg($repoUrl)
        ));

        $branches = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (preg_match('#refs/heads/(.+)$#', trim($line), $m)) {
                $branches[] = $m[1];
            }
        }

        return $branches;
    }

    /**
     * Switch an already-deployed site to a different branch: fetch it, check it out
     * into the live work tree, regenerate the post-receive hook (which hardcodes the
     * tracked branch) so future `git push` deploys land correctly, and fix ownership.
     * @throws Exception
     */
    public function switchBranch(Site $site, string $newBranch): void
    {
        $this->loadServerConfig($site);

        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $newBranch)) {
            throw new Exception("Invalid branch name: {$newBranch}");
        }

        $this->isOpencart = $site->framework == 'opencart_octopus' || $site->framework == 'opencart_default';
        $this->isLaravel = $site->framework == 'laravel';

        $site->addLog("🔀 Switching branch to '{$newBranch}'...");

        $domain = $site->domain;
        $workTree = "/home/{$site->hestia_username}/web/{$domain}/public_html";
        $gitDir = "/home/{$site->hestia_username}/git/{$domain}.git";
        $safeKeyName = str_replace(['.', '-', ' '], '_', $domain);
        $sshKeyPath = $this->resolveSshHomeDir() . "/.ssh/id_rsa_{$safeKeyName}";

        $gitCommands = "cd {$gitDir} && " .
            "GIT_SSH_COMMAND='ssh -i {$sshKeyPath} -o StrictHostKeyChecking=accept-new' git fetch origin {$newBranch} && " .
            "git --work-tree={$workTree} --git-dir={$gitDir} checkout -f {$newBranch}";

        $this->sshCommand($gitCommands);
        $site->addLog("✅ Checked out branch '{$newBranch}' on the server");

        $site->branch = $newBranch;
        $site->save();

        $this->createPostReceiveHook($site);
        $this->fixPermissions($site);

        $site->addLog("✅ Branch switch to '{$newBranch}' complete");
    }

    /**
     * Validate and sanitize site data
     * @throws Exception
     */
    protected function validateSite(Site $site): void
    {
        if (!preg_match('/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i', $site->domain)) {
            throw new Exception("Invalid domain format: {$site->domain}");
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\/\.]+$/', $site->branch)) {
            throw new Exception("Invalid branch name: {$site->branch}");
        }

        if (!in_array($site->php_version, ['7.4', '8.1', '8.2', '8.3', '8.4'])) {
            throw new Exception("Invalid PHP version: {$site->php_version}");
        }

        if (!preg_match('/^[a-z][a-z0-9_]{2,19}$/', $site->hestia_username)) {
            throw new Exception("Invalid HestiaCP username: {$site->hestia_username}");
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $site->database_name)) {
            throw new Exception("Invalid database name: {$site->database_name}");
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $site->database_user)) {
            throw new Exception("Invalid database user: {$site->database_user}");
        }
    }

    /**
     * @throws ConnectionException
     */
    public function provision(Site $site): void
    {
        try {
            $this->loadServerConfig($site);
            $this->validateSite($site);

            $site->update(['status' => 'provisioning']);
            $site->addLog("🚀 Starting provisioning for {$site->domain}/$this->serverHost");

            $this->isOpencart = $site->framework == 'opencart_octopus' || $site->framework == 'opencart_default';
            $this->isLaravel = $site->framework == 'laravel';

            $this->createHestiaUser($site);
            $this->createDomain($site);
            $this->createDatabase($site);
            $this->setupGitRepo($site);
            $this->deployCode($site);
            if (($this->isOpencart || $this->isLaravel) && $site->run_composer) {
                $this->runComposer($site);
            }

            $this->updateDocumentRoot($site);

            if ($site->ssl_enabled){
                $this->createSSLCertificate($site);
            }
            $this->createConfigFiles($site);
            $this->fixPermissions($site);

            $site->update(['status' => 'active']);
            $site->addLog("✅ Provisioning completed successfully!");
            $site->addLog("You must add remote Git repository as follows:");
            $site->addLog("git remote add @customName ssh://{$this->serverUser}@{$this->serverHost}/home/{$site->hestia_username}/git/{$site->domain}.git");
            $site->addLog("git push @customName {$site->branch}");

        } catch (Exception $e) {
            $site->update(['status' => 'failed']);
            $site->addLog("❌ ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Suspend or unsuspend a domain. Used outside the initial provisioning flow.
     * @throws Exception
     */
    public function setDomainSuspended(Site $site, bool $suspended): void
    {
        $this->loadServerConfig($site);

        $cmd = $suspended ? 'v-suspend-domain' : 'v-unsuspend-domain';
        $this->callHestia($cmd, [$site->hestia_username, $site->domain]);
    }

    /**
     * Renew (or issue) the Let's Encrypt certificate for an already-provisioned site.
     * @throws Exception
     */
    public function renewSSLCertificate(Site $site): void
    {
        $this->loadServerConfig($site);
        $this->createSSLCertificate($site);
    }

    /**
     * Tear down whatever a previous (failed) provisioning attempt created, so a retry
     * starts from a clean slate instead of tripping over leftover state (domain already
     * exists, database already exists, etc). Best-effort: each step is independent and
     * failures are logged rather than thrown, since earlier steps may never have run.
     */
    public function cleanup(Site $site): void
    {
        $this->loadServerConfig($site);

        $hasSibling = Site::where('hestia_server_id', $site->hestia_server_id)
            ->where('hestia_username', $site->hestia_username)
            ->where('id', '!=', $site->id)
            ->exists();

        if ($hasSibling) {
            // Other sites still live under this user — only remove this domain's own
            // web dir/database, never the user itself (that would take siblings down).
            try {
                $this->callHestia('v-delete-web-domain', [$site->hestia_username, $site->domain]);
                $site->addLog("🧹 Removed this domain's web resources (user shared with other sites, left intact)");
            } catch (Exception $e) {
                $site->addLog("ℹ️ Nothing to clean up for this domain (" . $e->getMessage() . ")");
            }

            try {
                $this->callHestia('v-delete-database', [$site->hestia_username, $site->database_name]);
            } catch (Exception $e) {
                // Ignore — database may never have been created yet.
            }

            try {
                $this->sshCommand("rm -rf /home/{$site->hestia_username}/git/{$site->domain}.git");
            } catch (Exception) {
                // Ignore.
            }

            return;
        }

        // Sole owner of this user — deleting it cascades to everything it owns: the
        // web domain, its database, SSL cert, cron jobs, and its entire
        // /home/{username} directory (which is also where the git bare repo lives).
        try {
            $this->callHestia('v-delete-user', [$site->hestia_username]);
            $site->addLog("🧹 Removed previous HestiaCP user and everything under it");
        } catch (Exception $e) {
            $site->addLog("ℹ️ Nothing to clean up on HestiaCP (" . $e->getMessage() . ")");
        }

        // Belt-and-braces: in case the user was deleted but the home directory or
        // database import artifacts survived for some reason.
        try {
            $this->sshCommand("rm -rf /home/{$site->hestia_username}");
        } catch (Exception) {
            // Ignore — most likely already gone via v-delete-user above.
        }
    }

    /**
     * Run an arbitrary shell command on the site's server via the privileged SSH account.
     * @throws Exception
     */
    public function sshExec(Site $site, string $command, int $timeout = 300): string
    {
        $this->loadServerConfig($site);

        return $this->sshCommand($command, $timeout);
    }

    /**
     * Absolute home directory of the privileged SSH account, for building deploy-key
     * paths without relying on tilde expansion inside quoted shell strings.
     * @throws Exception
     */
    public function sshHomeDir(Site $site): string
    {
        $this->loadServerConfig($site);

        return $this->resolveSshHomeDir();
    }

    protected function resolveSshHomeDir(): string
    {
        return $this->serverUser === 'root' ? '/root' : "/home/{$this->serverUser}";
    }

    /**
     * Relative path (inside a site's public_html) of the folder that holds user-uploaded
     * images/media for a given framework, or null if the framework isn't supported.
     */
    public static function imagesFolderForFramework(string $framework): ?string
    {
        return match ($framework) {
            'wordpress' => 'wp-content/uploads',
            'opencart_octopus', 'opencart_default' => 'upload/image',
            'laravel' => 'storage/app/public',
            default => null,
        };
    }

    /**
     * Load the stored SSH key as a private key, or null if unset, unparseable, or
     * actually a public key (a common mistake: pasting the .pub file by accident).
     */
    protected function loadSshPrivateKey(): ?\phpseclib3\Crypt\Common\PrivateKey
    {
        if (empty($this->serverSshKey)) {
            return null;
        }

        try {
            $key = \phpseclib3\Crypt\PublicKeyLoader::load($this->serverSshKey);
        } catch (\Exception $e) {
            logger()->warning("Stored SSH key failed to parse: " . $e->getMessage());
            return null;
        }

        if (!$key instanceof \phpseclib3\Crypt\Common\PrivateKey) {
            logger()->warning("Stored SSH key is a public key, not a private key — paste the private key file (e.g. ~/.ssh/id_rsa), not the .pub file.");
            return null;
        }

        return $key;
    }

    /**
     * Run a HestiaCP admin command directly as root over SSH. Root always has full
     * authority regardless of who owns the target resource (arg1), so this sidesteps
     * the HTTP API's per-access-key command scoping entirely.
     * @throws Exception
     */
    protected function callHestia(string $cmd, array $args): string
    {
        $escaped = array_map('escapeshellarg', $args);

        return $this->sshCommand('/usr/local/hestia/bin/' . $cmd . ' ' . implode(' ', $escaped));
    }

    /**
     * List non-suspended HestiaCP usernames that actually exist on the server, so
     * "attach to existing user" reflects reality instead of our local sites table.
     * @throws Exception
     */
    public function listUsers(HestiaServer $server): array
    {
        $this->applyServerConfig($server);

        $users = json_decode($this->callHestia('v-list-users', ['json']), true) ?? [];

        return collect($users)
            ->reject(fn ($u) => ($u['SUSPENDED'] ?? 'no') === 'yes')
            ->keys()
            ->values()
            ->all();
    }

    /**
     * Check the server directly for this username — a user can exist on Hestia with
     * no corresponding row in our sites table (created outside this app, or its only
     * site row was deleted), so the local table can't be trusted for this.
     */
    protected function hestiaUserExists(string $username): bool
    {
        try {
            $this->callHestia('v-list-user', [$username]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create an isolated HestiaCP user to own this domain's web/database resources.
     * @throws Exception
     */
    protected function createHestiaUser(Site $site): void
    {
        if ($this->hestiaUserExists($site->hestia_username)) {
            $site->addLog("👤 Attaching to existing HestiaCP user: {$site->hestia_username}");
            return;
        }

        $site->addLog("👤 Creating isolated HestiaCP user...");

        $this->callHestia('v-add-user', [
            $site->hestia_username,
            $site->hestia_user_password,
            "{$site->hestia_username}@{$site->domain}",
            $this->defaultPackage,
        ]);

        $site->addLog("✅ HestiaCP user created: {$site->hestia_username}");
    }

    /**
     * @throws Exception
     */
    protected function createDomain(Site $site): void
    {
        $site->addLog("📝 Creating domain in HestiaCP...");

        $this->callHestia('v-add-web-domain', [
            $site->hestia_username,
            $site->domain,
            $this->serverHost,
            $site->ssl_enabled ? 'yes' : 'no',
        ]);

        $site->addLog("✅ Domain created successfully:");
    }

    /**
     * @throws Exception
     */
    protected function createSSLCertificate(Site $site): void
    {
        $site->addLog("📝 Creating SSL Certificate...");

        $this->callHestia('v-add-letsencrypt-domain', [
            $site->hestia_username,
            $site->domain,
        ]);

        $site->ssl_enabled = true;
        $site->save();
        $site->addLog("✅ SSL Certificate created successfully");
    }

    /**
     * @throws Exception
     */
    protected function updateDocumentRoot(Site $site): void
    {
        $site->addLog("📝 Updating Document Root...");

        $this->callHestia('v-change-web-domain-docroot', [
            $site->hestia_username,
            $site->domain,
            $site->domain,
            $this->isOpencart ? 'upload' : ($this->isLaravel ? 'public' : ''),
            'default',
        ]);

        $site->addLog("✅ Document root updated successfully");
    }

    /**
     * @throws Exception
     */
    protected function createDatabase(Site $site): void
    {
        $site->addLog("🗄️ Creating database...");

        $this->callHestia('v-add-database', [
            $site->hestia_username,
            $site->database_name,
            $site->database_user,
            $site->database_password,
        ]);

        $site->addLog("✅ Database created: {$site->database_name}");
    }

    /**
     * Import database from uploaded ZIP file
     * @throws Exception
     */
    protected function importDatabase(Site $site): void
    {
        // Compute relative path
        $relativePath = 'database-imports/' . basename($site->database_file_path);
        $fullPath = Storage::disk('local')->path($relativePath);

        // Skip if no database file was uploaded
        if (empty($site->database_file_path) || !file_exists($fullPath)) {
            $site->addLog("ℹ️ No database file to import, skipping...");
            return;
        }

        $site->addLog("📥 Importing database from uploaded file...");

        try {
            $remoteZipPath = "/tmp/" . basename($fullPath);
            $remoteSqlPath = "/tmp/database_import_" . time() . ".sql";
            $extractDir = "/tmp/db_extract_" . time();

            // Upload the ZIP file to the server
            $site->addLog("Uploading database file to server...");
            $this->uploadFile($fullPath, $remoteZipPath);

            // Extract the ZIP file
            $site->addLog("Extracting database file...");
            $this->sshCommand("cd /tmp && unzip -o {$remoteZipPath} -d {$extractDir}");

            // Find the .sql file
            $sqlFile = trim($this->sshCommand("find {$extractDir} -name '*.sql' -type f | head -n 1"));

            if (empty($sqlFile)) {
                throw new Exception("No .sql file found in the uploaded ZIP archive");
            }

            $site->addLog("Found SQL file: " . basename($sqlFile));

            // Import the database
            $site->addLog("Importing SQL file into database...");
            $importCommand = "mysql -u {$site->hestia_username}_{$site->database_user} -p'{$site->database_password}' {$site->hestia_username}_{$site->database_name} < {$sqlFile}";
            $this->sshCommand($importCommand);

            // Clean up temporary files
            $site->addLog("Cleaning up temporary files...");
            $this->sshCommand("rm -rf {$remoteZipPath} {$extractDir}");

            $site->addLog("✅ Database imported successfully!");

        } catch (Exception $e) {
            $site->addLog("⚠️ Warning: Database import failed: " . $e->getMessage());
        }
    }

    /**
     * Upload a file to the server via SFTP
     * @throws Exception
     */
    protected function uploadFile(string $localPath, string $remotePath): void
    {
        try {
            // Verify local file exists
            if (!file_exists($localPath)) {
                throw new Exception("Local file does not exist: {$localPath}");
            }

            $ssh = new \phpseclib3\Net\SSH2($this->serverHost);
            $ssh->setTimeout(300);

            $authenticated = false;

            // Try SSH key authentication first if available
            if ($key = $this->loadSshPrivateKey()) {
                if ($ssh->login($this->serverUser, $key)) {
                    $authenticated = true;
                }
            }

            // Fall back to password authentication
            if (!$authenticated && !empty($this->serverPassword)) {
                if (!$ssh->login($this->serverUser, $this->serverPassword)) {
                    throw new Exception("SSH authentication failed for file upload");
                }
                $authenticated = true;
            }

            if (!$authenticated) {
                throw new Exception("SSH authentication failed. Tried key and password methods.");
            }

            // Create SFTP connection
            $sftp = new \phpseclib3\Net\SFTP($this->serverHost);

            // Login to SFTP
            if ($key = $this->loadSshPrivateKey()) {
                if (!$sftp->login($this->serverUser, $key)) {
                    // Try password auth
                    if (!$sftp->login($this->serverUser, $this->serverPassword)) {
                        throw new Exception("SFTP authentication failed");
                    }
                }
            } else {
                if (!$sftp->login($this->serverUser, $this->serverPassword)) {
                    throw new Exception("SFTP authentication failed");
                }
            }

            // Upload the file
            if (!$sftp->put($remotePath, $localPath, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE)) {
                throw new Exception("Failed to upload file via SFTP");
            }

        } catch (\Exception $e) {
            throw new Exception("File upload failed: {$e->getMessage()}");
        }
    }

    /**
     * Download a file from the site's server to a local path, streaming raw bytes
     * straight to disk via `cat` over exec() (no base64, no full in-memory buffering).
     * SFTP's get() is avoided here — it hits a phpseclib3 channel-tracking bug
     * ("Undefined array key 256") against this server, whereas plain exec() is reliable.
     * @throws Exception
     */
    public function downloadFile(Site $site, string $remotePath, string $localPath): void
    {
        $this->loadServerConfig($site);

        try {
            $ssh = new \phpseclib3\Net\SSH2($this->serverHost);
            $ssh->setTimeout(0);

            $authenticated = false;

            if ($key = $this->loadSshPrivateKey()) {
                if ($ssh->login($this->serverUser, $key)) {
                    $authenticated = true;
                }
            }

            if (!$authenticated && !empty($this->serverPassword)) {
                if ($ssh->login($this->serverUser, $this->serverPassword)) {
                    $authenticated = true;
                }
            }

            if (!$authenticated) {
                throw new Exception("SSH authentication failed. Tried key and password methods.");
            }

            $fp = fopen($localPath, 'wb');
            if (!$fp) {
                throw new Exception("Unable to open local file for writing: {$localPath}");
            }

            $ssh->exec('cat ' . escapeshellarg($remotePath), function ($chunk) use ($fp) {
                fwrite($fp, $chunk);
            });

            fclose($fp);

            $exitCode = $ssh->getExitStatus();
            if ($exitCode !== 0) {
                @unlink($localPath);
                throw new Exception("Remote cat command failed with exit code {$exitCode}");
            }
        } catch (\Exception $e) {
            throw new Exception("File download failed: {$e->getMessage()}");
        }
    }

    /**
     * @throws Exception
     */
    protected function setupGitRepo(Site $site): void
    {
        $site->addLog("📦 Setting up Git repository...");

        $domain = $site->domain;
        $repoPath = "/home/{$site->hestia_username}/git/{$domain}.git";

        $commands = [
            // Repos end up owned by the per-site user while every git command here
            // runs as root — without this, git's ownership safety check refuses
            // to touch them ("detected dubious ownership in repository").
            "git config --global --add safe.directory '*'",
            "mkdir -p {$repoPath}",
            "cd {$repoPath} && git init --bare",
            "chown -R {$site->hestia_username}:{$site->hestia_username} {$repoPath}",
        ];

        foreach ($commands as $cmd) {
            $this->sshCommand($cmd);
        }

        $this->createPostReceiveHook($site);

        $site->addLog("✅ Git repository created at {$repoPath}");
    }

    /**
     * @throws Exception
     */
    protected function createPostReceiveHook(Site $site): void
    {
        $domain = $site->domain;
        $hookPath = "/home/{$site->hestia_username}/git/{$domain}.git/hooks/post-receive";

        $hookContent = <<<'BASH'
#!/bin/bash

while read oldrev newrev refname
do
    branch=$(git rev-parse --symbolic --abbrev-ref $refname)

    if [ "$branch" = "BRANCH_PLACEHOLDER" ]; then
        echo "====================================="
        echo "Deploying BRANCH_PLACEHOLDER branch..."
        echo "====================================="

        WORK_TREE="/home/HESTIA_USERNAME_PLACEHOLDER/web/DOMAIN_PLACEHOLDER/public_html"

        git --work-tree=$WORK_TREE --git-dir=/home/HESTIA_USERNAME_PLACEHOLDER/git/DOMAIN_PLACEHOLDER.git checkout -f BRANCH_PLACEHOLDER

        cd $WORK_TREE

        # Create auth.json if needed
        if [ ! -f "$WORK_TREE/../auth.json" ]; then
            echo '{"gitlab-token":{"gitlab.com":"GITLAB_TOKEN_PLACEHOLDER"}}' > $WORK_TREE/../auth.json
            chmod 600 $WORK_TREE/../auth.json
            chown HESTIA_USERNAME_PLACEHOLDER:HESTIA_USERNAME_PLACEHOLDER $WORK_TREE/../auth.json
        fi

        # Fix permissions
        echo "Fixing permissions..."
        chown -R HESTIA_USERNAME_PLACEHOLDER:HESTIA_USERNAME_PLACEHOLDER /home/HESTIA_USERNAME_PLACEHOLDER/web/DOMAIN_PLACEHOLDER/public_html/
        find /home/HESTIA_USERNAME_PLACEHOLDER/web/DOMAIN_PLACEHOLDER/public_html/ -type f -exec chmod 644 {} \;
        find /home/HESTIA_USERNAME_PLACEHOLDER/web/DOMAIN_PLACEHOLDER/public_html/ -type d -exec chmod 755 {} \;
        chmod -R 775 $WORK_TREE/image/ /home/HESTIA_USERNAME_PLACEHOLDER/web/DOMAIN_PLACEHOLDER/public_html/storage/

        echo "✅ Deployment completed!"
    fi
done
BASH;

        // Replace placeholders
        $hookContent = str_replace('DOMAIN_PLACEHOLDER', $domain, $hookContent);
        $hookContent = str_replace('BRANCH_PLACEHOLDER', $site->branch, $hookContent);
        $hookContent = str_replace('HESTIA_USERNAME_PLACEHOLDER', $site->hestia_username, $hookContent);
        $hookContent = str_replace('GITLAB_TOKEN_PLACEHOLDER', $this->gitlabToken, $hookContent);

        // Use base64 to safely transfer the content
        $base64Content = base64_encode($hookContent);
        $this->sshCommand("echo '{$base64Content}' | base64 -d > {$hookPath}");
        $this->sshCommand("chmod +x {$hookPath}");
        $this->sshCommand("chown {$site->hestia_username}:{$site->hestia_username} {$hookPath}");

        $site->addLog("✅ Post-receive hook created");
    }

    /**
     * @throws Exception
     */
    protected function deployCode(Site $site): void
    {
        $site->addLog("🚀 Deploying code from Git...");

        $domain = $site->domain;
        $workTree = "/home/{$site->hestia_username}/web/{$domain}/public_html";
        $gitDir = "/home/{$site->hestia_username}/git/{$domain}.git";

        // Create domain-specific SSH key path (lives under the privileged SSH account's home)
        $safeKeyName = str_replace(['.', '-', ' '], '_', $domain);
        $sshKeyPath = $this->resolveSshHomeDir() . "/.ssh/id_rsa_{$safeKeyName}";

        $this->sshCommand("mkdir -p {$workTree}");
        $this->sshCommand("ssh-keyscan -H gitlab.com >> ~/.ssh/known_hosts 2>/dev/null || true");

        // Use domain-specific SSH key for Git operations
        $gitCommands = "cd {$gitDir} && " .
                      "git remote add origin {$site->repo_url} 2>/dev/null || true && " .
                      "GIT_SSH_COMMAND='ssh -i {$sshKeyPath} -o StrictHostKeyChecking=accept-new' git fetch origin {$site->branch} && " .
                      "git --work-tree={$workTree} --git-dir={$gitDir} checkout -f {$site->branch}";

        $this->sshCommand($gitCommands);

        $site->addLog("✅ Code deployed with SSH key: {$sshKeyPath}");
    }

    /**
     * @throws Exception
     */
    protected function runComposer(Site $site): void
    {
        $site->addLog("📦 Running Composer install...");

        $domain = $site->domain;
        $path = "/home/{$site->hestia_username}/web/{$domain}/public_html";
        $pathLaravel = "/home/{$site->hestia_username}/web/{$domain}/public_html/framework";

        $authJson = json_encode([
            'gitlab-token' => [
                'gitlab.com' => $this->gitlabToken
            ]
        ]);

        $this->sshCommand("echo '{$authJson}' > {$path}/auth.json");
        $this->sshCommand("chmod 600 {$path}/auth.json");
        $this->sshCommand("chown {$site->hestia_username}:{$site->hestia_username} {$path}/auth.json");

        if ($site->framework == 'opencart_default'){
            $this->sshCommand("cd {$path} && /usr/bin/php{$site->php_version} /usr/local/bin/composer install --ignore-platform-reqs");
        }elseif ($this->isLaravel){
            $this->sshCommand("cd {$path} && /usr/bin/php{$site->php_version} /usr/local/bin/composer install --no-dev --optimize-autoloader");
        }else{
            $this->sshCommand("cd {$path} && /usr/bin/php{$site->php_version} /usr/local/bin/composer install");
            $this->sshCommand("cd {$pathLaravel} && /usr/bin/php{$site->php_version} /usr/local/bin/composer install");
        }

        $site->addLog("✅ Composer dependencies installed");
    }

    /**
     * @throws Exception
     */
    protected function createConfigFiles(Site $site): void
    {
        $site->addLog("⚙️ Creating config files...");

        if ($site->framework === 'opencart_octopus') {
            $this->createOpenCartOctopusConfigFiles($site);
        }elseif ($site->framework === 'opencart_default') {
            $this->createOpenCartDefaultConfigFiles($site);
        } elseif ($site->framework === 'wordpress') {
            $this->createWordPressConfigFiles($site);
        } elseif ($site->framework === 'laravel') {
            $this->createLaravelConfigFiles($site);
        } else {
            throw new Exception("Unsupported framework: {$site->framework}");
        }

        $site->addLog("✅ Config files created");
    }

    /**
     * Create OpenCart configuration files
     * @throws Exception
     */
    protected function createOpenCartOctopusConfigFiles(Site $site): void
    {
        $domain = $site->domain;
        $basePath = "/home/{$site->hestia_username}/web/{$domain}/public_html";
        $uploadPath = "{$basePath}/upload";
        $storagePath = "{$basePath}/storage";
        $overwriteSuffix = $site->overwrite_suffix ?: 'demo';

        $templatePath = storage_path('config-templates/opencart/octopus');

        // Load and process config.php template
        $uploadConfig = file_get_contents("{$templatePath}/config.php");
        $uploadConfig = $this->replaceConfigPlaceholders($uploadConfig, [
            '{{DOMAIN}}' => $domain,
            '{{UPLOAD_PATH}}' => $uploadPath,
            '{{BASE_PATH}}' => $basePath,
            '{{STORAGE_PATH}}' => $storagePath,
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
        ]);

        // Load and process admin-config.php template
        $adminConfig = file_get_contents("{$templatePath}/admin-config.php");
        $adminConfig = $this->replaceConfigPlaceholders($adminConfig, [
            '{{DOMAIN}}' => $domain,
            '{{UPLOAD_PATH}}' => $uploadPath,
            '{{BASE_PATH}}' => $basePath,
            '{{STORAGE_PATH}}' => $storagePath,
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
        ]);

        // Load and process .env template
        $envContent = file_get_contents("{$templatePath}/oc.env");
        $envContent = $this->replaceConfigPlaceholders($envContent, [
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
            '{{OVERWRITE_SUFFIX}}' => $overwriteSuffix,
        ]);

        // Load and process framework.env template
        $frameworkEnvContent = file_get_contents("{$templatePath}/framework.env");
        $frameworkEnvContent = $this->replaceConfigPlaceholders($frameworkEnvContent, [
            '{{DOMAIN}}' => $domain,
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
        ]);

        // Write files using base64 encoding to avoid escaping issues
        $this->sshCommand("echo '" . base64_encode($uploadConfig) . "' | base64 -d > {$uploadPath}/config.php");
        $this->sshCommand("echo '" . base64_encode($adminConfig) . "' | base64 -d > {$uploadPath}/admin/config.php");
        $this->sshCommand("echo '" . base64_encode($envContent) . "' | base64 -d > {$basePath}/.env");
        $this->sshCommand("rm -rf  $basePath/index.html");
        $this->sshCommand("mkdir -p {$basePath}/framework && echo '" . base64_encode($frameworkEnvContent) . "' | base64 -d > {$basePath}/framework/.env");

        // Set proper permissions
        $this->sshCommand("chmod 644 {$uploadPath}/config.php {$uploadPath}/admin/config.php {$basePath}/.env {$basePath}/framework/.env");
        $this->sshCommand("chown {$site->hestia_username}:{$site->hestia_username} {$uploadPath}/config.php {$uploadPath}/admin/config.php {$basePath}/.env {$basePath}/framework/.env");
    }

    /**
     * Create OpenCart configuration files
     * @throws Exception
     */
    protected function createOpenCartDefaultConfigFiles(Site $site): void
    {
        $domain = $site->domain;
        $basePath = "/home/{$site->hestia_username}/web/{$domain}/public_html";
        $uploadPath = "{$basePath}/upload";
        $storagePath = "{$basePath}/storage";
        $overwriteSuffix = $site->overwrite_suffix ?: 'demo';

        $templatePath = storage_path('config-templates/opencart/default');

        $uploadConfig = file_get_contents("{$templatePath}/config.php");
        $uploadConfig = $this->replaceConfigPlaceholders($uploadConfig, [
            '{{DOMAIN}}' => $domain,
            '{{UPLOAD_PATH}}' => $uploadPath,
            '{{BASE_PATH}}' => $basePath,
            '{{STORAGE_PATH}}' => $storagePath,
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
        ]);

        $adminConfig = file_get_contents("{$templatePath}/admin-config.php");
        $adminConfig = $this->replaceConfigPlaceholders($adminConfig, [
            '{{DOMAIN}}' => $domain,
            '{{UPLOAD_PATH}}' => $uploadPath,
            '{{BASE_PATH}}' => $basePath,
            '{{STORAGE_PATH}}' => $storagePath,
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
        ]);

        $envContent = file_get_contents("{$templatePath}/oc.env");
        $envContent = $this->replaceConfigPlaceholders($envContent, [
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
            '{{OVERWRITE_SUFFIX}}' => $overwriteSuffix,
        ]);

        // Write files using base64 encoding to avoid escaping issues
        $this->sshCommand("echo '" . base64_encode($uploadConfig) . "' | base64 -d > {$uploadPath}/config.php");
        $this->sshCommand("echo '" . base64_encode($adminConfig) . "' | base64 -d > {$uploadPath}/admin/config.php");
        $this->sshCommand("echo '" . base64_encode($envContent) . "' | base64 -d > {$basePath}/.env");
        $this->sshCommand("rm -rf  $basePath/index.html");

        // Set proper permissions
        $this->sshCommand("chmod 644 {$uploadPath}/config.php {$uploadPath}/admin/config.php {$basePath}/.env");
        $this->sshCommand("chown {$site->hestia_username}:{$site->hestia_username} {$uploadPath}/config.php {$uploadPath}/admin/config.php {$basePath}/.env");
    }

    /**
     * Create WordPress configuration files
     * @throws Exception
     */
    protected function createWordPressConfigFiles(Site $site): void
    {
        $domain = $site->domain;
        $basePath = "/home/{$site->hestia_username}/web/{$domain}/public_html";

        $templatePath = storage_path('config-templates/wordpress');

        $wpConfig = file_get_contents("{$templatePath}/wp-config.php");
        $wpConfig = $this->replaceConfigPlaceholders($wpConfig, [
            '{{DOMAIN}}' => $domain,
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
            '{{AUTH_KEY}}' => $this->generateWpSalt(),
            '{{SECURE_AUTH_KEY}}' => $this->generateWpSalt(),
            '{{LOGGED_IN_KEY}}' => $this->generateWpSalt(),
            '{{NONCE_KEY}}' => $this->generateWpSalt(),
            '{{AUTH_SALT}}' => $this->generateWpSalt(),
            '{{SECURE_AUTH_SALT}}' => $this->generateWpSalt(),
            '{{LOGGED_IN_SALT}}' => $this->generateWpSalt(),
            '{{NONCE_SALT}}' => $this->generateWpSalt(),
        ]);

        // Write wp-config.php to root directory
        $this->sshCommand("echo '" . base64_encode($wpConfig) . "' | base64 -d > {$basePath}/wp-config.php");

        // Set proper permissions
        $this->sshCommand("chmod 644 {$basePath}/wp-config.php");
        $this->sshCommand("chown {$site->hestia_username}:{$site->hestia_username} {$basePath}/wp-config.php");
        $this->sshCommand("rm -rf  $basePath/index.html");
        // If database was imported, update site URLs in wp_options table
        if (!empty($site->database_file_path) && file_exists($site->database_file_path)) {
            $this->updateWordPressSiteUrls($site);
        }
    }

    /**
     * Update WordPress site URLs in options table
     * @throws Exception
     */
    protected function updateWordPressSiteUrls(Site $site): void
    {
        $site->addLog("🔄 Updating WordPress site URLs in database...");

        $newUrl = "https://{$site->domain}";

        $updateSiteUrl = "UPDATE wp_options SET option_value = '{$newUrl}' WHERE option_name = 'siteurl';";
        $updateHomeUrl = "UPDATE wp_options SET option_value = '{$newUrl}' WHERE option_name = 'home';";

        $sqlCommand = "mysql -u {$site->hestia_username}_{$site->database_user} -p'{$site->database_password}' {$site->hestia_username}_{$site->database_name} -e \"{$updateSiteUrl} {$updateHomeUrl}\"";

        try {
            $this->sshCommand($sqlCommand);
            $site->addLog("✅ WordPress site URLs updated to {$newUrl}");
        } catch (Exception $e) {
            $site->addLog("⚠️ Warning: Failed to update WordPress site URLs: " . $e->getMessage());
            // Don't throw - allow provisioning to continue
        }
    }

    /**
     * Create Laravel configuration files (.env)
     * @throws Exception
     */
    protected function createLaravelConfigFiles(Site $site): void
    {
        $domain = $site->domain;
        $basePath = "/home/{$site->hestia_username}/web/{$domain}/public_html";

        $templatePath = storage_path('config-templates/laravel');

        $envContent = file_get_contents("{$templatePath}/laravel.env");
        $envContent = $this->replaceConfigPlaceholders($envContent, [
            '{{APP_URL}}' => "https://{$domain}",
            '{{APP_KEY}}' => 'base64:' . base64_encode(random_bytes(32)),
            '{{DB_USER}}' => "{$site->hestia_username}_{$site->database_user}",
            '{{DB_PASSWORD}}' => $site->database_password,
            '{{DB_NAME}}' => "{$site->hestia_username}_{$site->database_name}",
        ]);

        $this->sshCommand("echo '" . base64_encode($envContent) . "' | base64 -d > {$basePath}/.env");
        $this->sshCommand("chmod 644 {$basePath}/.env");
        $this->sshCommand("chown {$site->hestia_username}:{$site->hestia_username} {$basePath}/.env");
        $this->sshCommand("rm -rf {$basePath}/index.html");

        if ($site->run_composer) {
            $this->sshCommand("cd {$basePath} && /usr/bin/php{$site->php_version} artisan storage:link --force");
        }
    }

    /**
     * Replace placeholders in config templates
     */
    protected function replaceConfigPlaceholders(string $content, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Generate WordPress salt string
     */
    protected function generateWpSalt(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
        $salt = '';
        for ($i = 0; $i < 64; $i++) {
            $salt .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $salt;
    }

    /**
     * @throws Exception
     */
    protected function fixPermissions(Site $site): void
    {
        $site->addLog("🔧 Fixing permissions...");

        $domain = $site->domain;
        $basePath = "/home/{$site->hestia_username}/web/{$domain}/public_html";

        if ($this->isOpencart) {
            $commands = [
                "mkdir -p {$basePath}/storage/{cache,logs}",
                "mkdir -p {$basePath}/upload/image",
                "chown -R {$site->hestia_username}:{$site->hestia_username} {$basePath}",
                "find {$basePath} -type f -exec chmod 644 {} \;",
                "find {$basePath} -type d -exec chmod 755 {} \;",
                "chmod -R 775 {$basePath}/storage/",
                "chmod -R 775 {$basePath}/upload/image/",
            ];
        } else {
            // Every other framework (WordPress etc): git checkout runs as root during
            // deployCode(), so without this every deployed file stays root-owned
            // instead of belonging to the site's isolated HestiaCP user.
            $commands = [
                "chown -R {$site->hestia_username}:{$site->hestia_username} {$basePath}",
                "find {$basePath} -type f -exec chmod 644 {} \;",
                "find {$basePath} -type d -exec chmod 755 {} \;",
            ];

            if ($site->framework === 'wordpress') {
                $commands[] = "chmod -R 775 {$basePath}/wp-content/ 2>/dev/null || true";
            }

            if ($this->isLaravel) {
                $commands[] = "chmod -R 775 {$basePath}/storage/ {$basePath}/bootstrap/cache/ 2>/dev/null || true";
            }
        }

        foreach ($commands as $cmd) {
            $this->sshCommand($cmd);
        }

        $site->addLog("✅ Permissions fixed");
    }

    /**
     * Execute SSH command using phpseclib
     * @throws Exception
     */
    protected function sshCommand(string $command, int $timeout = 300): string
    {
        logger()->debug("Executing SSH command", [
            'host' => $this->serverHost,
            'user' => $this->serverUser,
            'command' => substr($command, 0, 100)
        ]);

        try {
            $ssh = new \phpseclib3\Net\SSH2($this->serverHost);
            $ssh->setTimeout($timeout);
            $authenticated = false;

            if ($key = $this->loadSshPrivateKey()) {
                if ($ssh->login($this->serverUser, $key)) {
                    $authenticated = true;
                }
            }

            if (!$authenticated && !empty($this->serverPassword)) {
                if ($ssh->login($this->serverUser, $this->serverPassword)) {
                    $authenticated = true;
                    logger()->debug("SSH authenticated with password");
                } else {
                    $errors = $ssh->getErrors();
                    logger()->error("SSH password auth failed", ['errors' => $errors]);
                }
            }

            if (!$authenticated) {
                throw new Exception("SSH authentication failed. Tried key and password methods.");
            }

            $output = $ssh->exec($command);
            $exitCode = $ssh->getExitStatus();

            if ($exitCode !== 0) {
                logger()->error("SSH command failed", [
                    'command' => $command,
                    'output' => substr($output, 0, 500),
                    'exit_code' => $exitCode
                ]);

                throw new Exception(
                    "SSH command failed: {$output}\nExit code: {$exitCode}"
                );
            }

            return trim($output);

        } catch (\Exception $e) {
            logger()->error("SSH connection failed", [
                'host' => $this->serverHost,
                'user' => $this->serverUser,
                'error' => $e->getMessage()
            ]);

            throw new Exception("SSH connection failed: {$e->getMessage()}");
        }
    }
}
