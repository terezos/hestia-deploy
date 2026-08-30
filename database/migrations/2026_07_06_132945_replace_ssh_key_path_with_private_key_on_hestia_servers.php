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
        Schema::table('hestia_servers', function (Blueprint $table) {
            $table->text('ssh_private_key')->nullable()->after('ssh_password');
            $table->dropColumn('ssh_key_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hestia_servers', function (Blueprint $table) {
            $table->string('ssh_key_path')->nullable()->after('ssh_password');
            $table->dropColumn('ssh_private_key');
        });
    }
};
