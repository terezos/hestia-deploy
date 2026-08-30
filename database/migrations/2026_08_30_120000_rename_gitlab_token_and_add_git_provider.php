<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hestia_servers', function (Blueprint $table) {
            $table->renameColumn('gitlab_token', 'git_token');
        });

        Schema::table('sites', function (Blueprint $table) {
            // Null = derive the Composer auth scheme from the repo URL host.
            $table->string('git_provider')->nullable()->after('repo_url');
        });
    }

    public function down(): void
    {
        Schema::table('hestia_servers', function (Blueprint $table) {
            $table->renameColumn('git_token', 'gitlab_token');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('git_provider');
        });
    }
};
