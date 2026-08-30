<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    protected $signature = 'create:user
        {email? : Email address}
        {--name= : Display name, defaults to the part before the @}
        {--password= : Password, generated and printed when omitted}
        {--role=maintainer : Role to assign (admin or maintainer)}';

    protected $description = 'Create a panel user and assign a role';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email address');
        $role = $this->option('role');
        $password = $this->option('password') ?: Str::password(16);

        $validator = Validator::make([
            'email' => $email,
            'role' => $role,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->option('name') ?: Str::before($email, '@'),
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole($role);

        $this->info("Created {$user->email} with role {$role}.");

        if (! $this->option('password')) {
            $this->line("Password: {$password}");
        }

        return self::SUCCESS;
    }
}
