<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Panel searches compare through this collation (database.connections.pgsql.search_collation), so "frances" finds "Francés" and "nunez" finds "Núñez".
    public function up(): void
    {
        DB::statement("CREATE COLLATION IF NOT EXISTS sin_tildes (provider = icu, locale = 'und-u-ks-level1', deterministic = false)");
    }

    public function down(): void
    {
        DB::statement('DROP COLLATION IF EXISTS sin_tildes');
    }
};
