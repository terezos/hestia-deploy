<?php

namespace App\Console\Commands;

use App\Models\SiteBackup;
use Illuminate\Console\Command;

class PruneOldBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-old-backups {--days=7 : Delete backups older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete database/images backup files (and their records) older than N days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = (int) $this->option('days');

        $backups = SiteBackup::where('created_at', '<', now()->subDays($days))->get();

        foreach ($backups as $backup) {
            $backup->delete();
        }

        $this->info("Pruned {$backups->count()} backup(s) older than {$days} day(s).");
    }
}
