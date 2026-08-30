<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\SiteBackup;
use App\Services\ProvisioningService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(protected int $siteId, protected int $backupId)
    {
    }

    public function handle(ProvisioningService $provisioning): void
    {
        $site = Site::findOrFail($this->siteId);
        $backup = SiteBackup::findOrFail($this->backupId);

        $dbName = "{$site->hestia_username}_{$site->database_name}";
        $dbUser = "{$site->hestia_username}_{$site->database_user}";
        $dbPassword = $site->database_password;
        $domainPath = "/home/{$site->hestia_username}/web/{$site->domain}/public_html";
        $timestamp = time();
        $sqlFile = $domainPath . '/' . $dbName . '_' . $timestamp . '.sql';
        $zipFile = $domainPath . '/' . $dbName . '_' . $timestamp . '.zip';

        try {
            $site->addLog("💾 Database backup started (queued job)...");

            $dumpCommand = sprintf(
                "cd %s && mysqldump -u%s -p'%s' %s > %s && zip -q %s %s && echo 'SUCCESS' || echo 'FAILED'",
                escapeshellarg($domainPath),
                escapeshellarg($dbUser),
                str_replace("'", "'\\''", $dbPassword),
                escapeshellarg($dbName),
                escapeshellarg(basename($sqlFile)),
                escapeshellarg(basename($zipFile)),
                escapeshellarg(basename($sqlFile))
            );

            $output = $provisioning->sshExec($site, $dumpCommand, 1800);

            if (!str_contains($output, 'SUCCESS')) {
                throw new Exception('mysqldump/zip failed: ' . substr($output, 0, 500));
            }

            $filename = "{$site->domain}_{$timestamp}.zip";
            $relativePath = "backups/site_{$site->id}/{$filename}";

            Storage::disk('local')->makeDirectory("backups/site_{$site->id}");
            $provisioning->downloadFile($site, $zipFile, Storage::disk('local')->path($relativePath));

            $this->cleanupRemote($provisioning, $site, [$zipFile, $sqlFile]);

            $backup->update([
                'status' => 'completed',
                'path' => $relativePath,
                'filename' => $filename,
                'size' => Storage::disk('local')->size($relativePath),
            ]);

            $site->addLog("✅ Database backup completed: {$filename}");
        } catch (Exception $e) {
            $this->cleanupRemote($provisioning, $site, [$zipFile, $sqlFile]);
            $backup->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $site->addLog("❌ Database backup failed: " . $e->getMessage());
        }
    }

    protected function cleanupRemote(ProvisioningService $provisioning, Site $site, array $remotePaths): void
    {
        try {
            $provisioning->sshExec($site, 'rm -f ' . implode(' ', array_map('escapeshellarg', $remotePaths)));
        } catch (Exception) {
            // Best-effort — server may already be unreachable if the job failed because of that.
        }
    }
}
