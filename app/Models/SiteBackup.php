<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SiteBackup extends Model
{
    protected $fillable = [
        'site_id',
        'type',
        'status',
        'path',
        'filename',
        'size',
        'error',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function delete(): ?bool
    {
        if ($this->path && Storage::disk('local')->exists($this->path)) {
            Storage::disk('local')->delete($this->path);
        }

        return parent::delete();
    }
}
