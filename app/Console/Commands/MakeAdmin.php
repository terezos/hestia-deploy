<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'hestia:make-admin {email}';

    protected $description = 'Grant the admin role to an existing user by email';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $user->assignRole('admin');

        $this->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
