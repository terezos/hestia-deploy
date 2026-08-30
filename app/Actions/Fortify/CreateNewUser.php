<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => array_filter([
                'required', 'string', 'email', 'max:255', 'unique:users',
                $this->allowedDomainRule(),
            ]),
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $user->assignRole('maintainer');

        return $user;
    }

    /**
     * Restrict self-registration to the configured email domains, if any.
     */
    protected function allowedDomainRule(): ?string
    {
        $domains = config('hestia.allowed_email_domains', []);

        if (empty($domains)) {
            return null;
        }

        $pattern = implode('|', array_map(
            fn (string $domain) => preg_quote($domain, '/'),
            $domains
        ));

        return 'regex:/^[\\w\\.\\-\\+]+@(' . $pattern . ')$/i';
    }
}
