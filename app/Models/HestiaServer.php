<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HestiaServer extends Model
{
    protected $fillable = [
        'name',
        'panel_url',
        'access_key',
        'secret_key',
        'ssh_host',
        'ssh_user',
        'ssh_password',
        'ssh_private_key',
        'gitlab_token',
        'default_package',
        'is_active',
    ];

    protected $casts = [
        'access_key' => 'encrypted',
        'secret_key' => 'encrypted',
        'ssh_password' => 'encrypted',
        'ssh_private_key' => 'encrypted',
        'gitlab_token' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_key',
        'secret_key',
        'ssh_password',
        'ssh_private_key',
        'gitlab_token',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
