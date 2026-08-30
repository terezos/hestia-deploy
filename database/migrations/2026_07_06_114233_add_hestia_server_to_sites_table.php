<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('hestia_server_id')->nullable()->after('id')->constrained('hestia_servers')->nullOnDelete();
            $table->string('hestia_username')->nullable()->after('domain');
            $table->text('hestia_user_password')->nullable()->after('hestia_username');
        });

        $defaultServer = DB::table('hestia_servers')->where('name', 'Default Server')->first();

        if ($defaultServer) {
            // Legacy sites all lived under the one shared system/HestiaCP user that
            // provisioning used to hardcode — that's the ssh_user, not an API auth
            // identity (there's no per-user API auth in the token model).
            DB::table('sites')->update([
                'hestia_server_id' => $defaultServer->id,
                'hestia_username' => $defaultServer->ssh_user,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hestia_server_id');
            $table->dropColumn(['hestia_username', 'hestia_user_password']);
        });
    }
};
