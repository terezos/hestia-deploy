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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('repo_url');
            $table->string('branch')->default('developer');
            $table->string('database_name');
            $table->string('database_user');
            $table->string('database_password');
            $table->string('overwrite_suffix')->nullable();
            $table->string('database_file_path')->nullable();
            $table->enum('framework', ['opencart_octopus','opencart_default', 'wordpress'])->default('opencart_octopus');
            $table->enum('php_version', ['7.4', '8.2', '8.3', '8.4'])->default('8.2');
            $table->boolean('ssl_enabled')->default(true);
            $table->enum('status', ['pending', 'provisioning', 'active', 'failed', 'suspended'])->default('pending');
            $table->text('log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
