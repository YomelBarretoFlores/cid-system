<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Receipts stored before this change keep no fingerprint; a rejected payment frees its file, like its operation number.
        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->char('recibo_sha256', 64)->nullable();
        });

        DB::statement("ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_recibo_sha256_check CHECK (recibo_sha256 IS NULL OR recibo_sha256 ~ '^[0-9a-f]{64}$')");
        DB::statement("CREATE UNIQUE INDEX pag_pagos_recibo_sha256_unique ON pag_pagos (recibo_sha256) WHERE estado <> 'rechazado' AND recibo_sha256 IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS pag_pagos_recibo_sha256_unique');
        DB::statement('ALTER TABLE pag_pagos DROP CONSTRAINT IF EXISTS pag_pagos_recibo_sha256_check');

        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->dropColumn('recibo_sha256');
        });
    }
};
