<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sites = DB::table('sites')->whereNotNull('log')->get();

        foreach ($sites as $site) {
            if (!empty($site->log)) {
                $logFile = "logs/{$site->domain}.log";
                Storage::disk('local')->put($logFile, $site->log);
                echo "Migrated logs for {$site->domain}\n";
            }
        }

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add the log column back
        Schema::table('sites', function (Blueprint $table) {
            $table->text('log')->nullable();
        });

        // Migrate logs back from files to database
        $sites = DB::table('sites')->get();

        foreach ($sites as $site) {
            $logFile = "logs/{$site->domain}.log";

            if (Storage::disk('local')->exists($logFile)) {
                $logContent = Storage::disk('local')->get($logFile);
                DB::table('sites')->where('id', $site->id)->update(['log' => $logContent]);
                echo "Restored logs for {$site->domain}\n";
            }
        }
    }
};
