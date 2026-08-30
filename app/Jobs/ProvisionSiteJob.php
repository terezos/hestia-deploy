<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\ProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Provisioning cannot run in the web request: v-add-web-domain reloads the web
 * server that is serving that very request, killing the worker mid-flight.
 */
class ProvisionSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // A full provision (user, domain, database, clone, composer, SSL) takes minutes.
    public int $timeout = 3600;

    // Not idempotent — a half-finished provision must be retried by hand.
    public int $tries = 1;

    public function __construct(public int $siteId)
    {
    }

    public function handle(ProvisioningService $provisioning): void
    {
        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $provisioning->provision($site);
    }

    public function failed(\Throwable $e): void
    {
        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $site->update(['status' => 'failed']);
        $site->addLog("❌ Provisioning job failed: " . $e->getMessage());
    }
}
