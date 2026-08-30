<?php

namespace Tests\Feature;

use App\Models\HestiaServer;
use App\Models\Site;
use App\Services\ProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComposerAuthJsonTest extends TestCase
{
    use RefreshDatabase;

    /** Reach the protected helper without provisioning anything. */
    private function authJsonFor(?string $token, string $repoUrl, ?string $provider): ?string
    {
        $server = HestiaServer::create([
            'name' => 'test',
            'panel_url' => 'https://panel.example.com:8083',
            'access_key' => 'access',
            'secret_key' => 'secret',
            'ssh_host' => 'ssh.example.com',
            'ssh_user' => 'root',
            'git_token' => $token,
        ]);

        $site = new Site(['repo_url' => $repoUrl, 'git_provider' => $provider]);

        $service = new ProvisioningService();
        $reflection = new \ReflectionClass($service);

        $load = $reflection->getMethod('applyServerConfig');
        $load->setAccessible(true);
        $load->invoke($service, $server);

        $method = $reflection->getMethod('composerAuthJson');
        $method->setAccessible(true);

        return $method->invoke($service, $site);
    }

    public function test_it_maps_the_provider_to_the_composer_auth_scheme(): void
    {
        $this->assertSame(
            '{"gitlab-token":{"gitlab.com":"tok"}}',
            $this->authJsonFor('tok', 'git@gitlab.com:acme/shop.git', null)
        );

        $this->assertSame(
            '{"github-oauth":{"github.com":"tok"}}',
            $this->authJsonFor('tok', 'git@github.com:acme/shop.git', null)
        );

        $this->assertSame(
            '{"http-basic":{"bitbucket.org":{"username":"x-token-auth","password":"tok"}}}',
            $this->authJsonFor('tok', 'https://bitbucket.org/acme/shop.git', null)
        );

        // Self-hosted host, provider chosen per site.
        $this->assertSame(
            '{"github-oauth":{"git.acme.dev":"tok"}}',
            $this->authJsonFor('tok', 'git@git.acme.dev:acme/shop.git', 'github')
        );
    }

    public function test_it_writes_no_auth_json_without_a_token(): void
    {
        $this->assertNull($this->authJsonFor(null, 'git@gitlab.com:acme/shop.git', null));
    }
}
