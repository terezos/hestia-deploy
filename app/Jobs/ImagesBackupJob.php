<?php

namespace App\Jobs;

use App\Models\Site;
use App\Models\SiteBackup;
use App\Services\ProvisioningService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ImagesBackupJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(protected int $siteId, protected int $backupId)
    {
    }

    public function handle(ProvisioningService $provisioning): void
    {
        $site = Site::findOrFail($this->siteId);
        $backup = SiteBackup::findOrFail($this->backupId);

        $basePath = "/home/{$site->hestia_username}/web/{$site->domain}/public_html";
        $imagesFolder = ProvisioningService::imagesFolderForFramework($site->framework);
        $timestamp = time();
        $archiveName = "images_{$timestamp}.tar.gz";
        $archivePath = $basePath . '/' . $archiveName;

        try {
            if (!$imagesFolder) {
                throw new Exception("No images folder mapping for framework: {$site->framework}");
            }

            $site->addLog("🖼️ Images backup started (queued job)...");

            $archiveCommand = sprintf(
                "cd %s && [ -d %s ] && tar -czf %s %s && echo 'SUCCESS' || echo 'FAILED'",
                escapeshellarg($basePath),
                escapeshellarg($imagesFolder),
                escapeshellarg($archiveName),
                escapeshellarg($imagesFolder)
            );

            $output = $provisioning->sshExec($site, $archiveCommand, 3600);

            if (!str_contains($output, 'SUCCESS')) {
                throw new Exception("Images folder not found or archive failed ({$imagesFolder})");
            }

            $filename = "{$site->domain}_images_{$timestamp}.tar.gz";
            $relativePath = "backups/site_{$site->id}/{$filename}";

            Storage::disk('local')->makeDirectory("backups/site_{$site->id}");
            $provisioning->downloadFile($site, $archivePath, Storage::disk('local')->path($relativePath));

            $this->cleanupRemote($provisioning, $site, [$archivePath]);

            $backup->update([
                'status' => 'completed',
                'path' => $relativePath,
                'filename' => $filename,
                'size' => Storage::disk('local')->size($relativePath),
            ]);

            $site->addLog("✅ Images backup completed: {$filename}");
        } catch (Exception $e) {
            $this->cleanupRemote($provisioning, $site, [$archivePath]);
            $backup->update(['status' => 'failed', 'error' => $e->getMessage()]);
            $site->addLog("❌ Images backup failed: " . $e->getMessage());
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
