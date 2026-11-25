<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const int POSTGRES_18 = 180000;

    // PostgreSQL before 18 rejects LIKE on nondeterministic collations, so every panel search failed there; a deterministic
    // collation keeps searches working (accent-sensitive) until the server runs PostgreSQL 18.
    public function up(): void
    {
        if ($this->servidorSoportaLikeSinTildes()) {
            return;
        }

        $this->recrear(determinista: true);
    }

    public function down(): void
    {
        if ($this->servidorSoportaLikeSinTildes()) {
            return;
        }

        $this->recrear(determinista: false);
    }

    private function servidorSoportaLikeSinTildes(): bool
    {
        return (int) DB::scalar("select current_setting('server_version_num')") >= self::POSTGRES_18;
    }

    private function recrear(bool $determinista): void
    {
        $valor = $determinista ? 'true' : 'false';

        DB::statement('DROP COLLATION IF EXISTS sin_tildes');
        DB::statement("CREATE COLLATION sin_tildes (provider = icu, locale = 'und-u-ks-level1', deterministic = {$valor})");
    }
};
