<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hestia_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('panel_url');
            $table->text('access_key');
            $table->text('secret_key');
            $table->string('ssh_host');
            $table->string('ssh_user');
            $table->text('ssh_password')->nullable();
            $table->string('ssh_key_path')->nullable();
            $table->text('gitlab_token')->nullable();
            $table->string('default_package')->default('default');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hestia_servers');
    }
};
