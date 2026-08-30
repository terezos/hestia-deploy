<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ponytail: MySQL-only ENUM widening. SQLite has no ENUM (the column is
        // plain text there), so the test database needs no change.
        if (! $this->supportsEnum()) {
            return;
        }

        DB::statement("ALTER TABLE sites MODIFY framework ENUM('opencart_octopus','opencart_default','wordpress','laravel') NOT NULL DEFAULT 'opencart_octopus'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->supportsEnum()) {
            return;
        }

        DB::statement("ALTER TABLE sites MODIFY framework ENUM('opencart_octopus','opencart_default','wordpress') NOT NULL DEFAULT 'opencart_octopus'");
    }

    private function supportsEnum(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
