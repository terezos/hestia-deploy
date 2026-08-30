<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Site extends Model
{
    protected $fillable = [
        'domain',
        'repo_url',
        'git_provider',
        'branch',
        'database_name',
        'database_user',
        'database_password',
        'overwrite_suffix',
        'framework',
        'run_composer',
        'database_file_path',
        'php_version',
        'ssl_enabled',
        'status',
        'webhook_token',
        'hestia_server_id',
        'hestia_username',
        'hestia_user_password',
    ];

    protected $casts = [
        'ssl_enabled' => 'boolean',
        'run_composer' => 'boolean',
        'hestia_user_password' => 'encrypted',
    ];

    protected $hidden = [
        'database_password',
        'hestia_user_password',
    ];

    public function hestiaServer(): BelongsTo
    {
        return $this->belongsTo(HestiaServer::class);
    }

    public function backups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SiteBackup::class);
    }

    public function addLog(string $message): void
    {
        $logMessage = "[" . now() . "] " . $message;
        $logFile = "logs/{$this->domain}.log";

        if (!Storage::disk('local')->exists('logs')) {
            Storage::disk('local')->makeDirectory('logs');
        }

        Storage::disk('local')->append($logFile, $logMessage);
    }

    public function getLog(): string
    {
        $logFile = "logs/{$this->domain}.log";

        if (Storage::disk('local')->exists($logFile)) {
            return Storage::disk('local')->get($logFile);
        }

        return '';
    }
}
