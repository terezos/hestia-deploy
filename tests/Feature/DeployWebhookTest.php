<?php

namespace Tests\Feature;

use App\Models\HestiaServer;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function site(): Site
    {
        $server = HestiaServer::create([
            'name' => 'test',
            'panel_url' => 'https://hestia.test:8083',
            'access_key' => 'access',
            'secret_key' => 'secret',
            'ssh_host' => 'hestia.test',
            'ssh_user' => 'root',
            'ssh_private_key' => 'dummy',
            'is_active' => true,
        ]);

        return Site::create([
            'domain' => 'example.test',
            'repo_url' => 'git@example.test:acme/site.git',
            'branch' => 'main',
            'framework' => 'laravel',
            'database_name' => 'db_abc123',
            'database_user' => 'u_abc123',
            'database_password' => 'secret',
            'php_version' => '8.3',
            'status' => 'active',
            'webhook_token' => 'correct-url-token',
            'hestia_server_id' => $server->id,
            'hestia_username' => 'exampletest_ab12',
        ]);
    }

    public function test_it_rejects_a_wrong_url_token(): void
    {
        $site = $this->site();

        $this->postJson("/webhook/{$site->id}/wrong-token", [], [
            'X-Gitlab-Token' => config('hestia.webhook_header_token'),
        ])->assertStatus(403);
    }

    public function test_it_rejects_a_wrong_header_token(): void
    {
        $site = $this->site();

        $this->postJson("/webhook/{$site->id}/{$site->webhook_token}", [], [
            'X-Gitlab-Token' => 'nope',
        ])->assertStatus(403);
    }

    public function test_it_rejects_a_missing_header_token(): void
    {
        $site = $this->site();

        $this->postJson("/webhook/{$site->id}/{$site->webhook_token}")
            ->assertStatus(403);
    }
}
