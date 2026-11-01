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
        // Rows already stored get false without an UPDATE (which the lifecycle trigger would refuse) and claim no voucher check; new rows require it.
        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->boolean('exige_boleta_original')->default(false);
            $table->foreignId('boleta_original_verificada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('boleta_original_verificada_en')->nullable();
            $table->text('justificacion_monto')->nullable();

            $table->index('boleta_original_verificada_por');
        });

        DB::statement('ALTER TABLE pag_pagos ALTER COLUMN exige_boleta_original SET DEFAULT true');
        DB::statement("ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_boleta_original_check CHECK (
            (boleta_original_verificada_por IS NULL) = (boleta_original_verificada_en IS NULL)
            AND (NOT exige_boleta_original OR estado IN ('pendiente', 'rechazado') OR boleta_original_verificada_por IS NOT NULL)
        )");
        DB::statement("ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_justificacion_monto_check CHECK (justificacion_monto IS NULL OR btrim(justificacion_monto) <> '')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pag_pagos DROP CONSTRAINT IF EXISTS pag_pagos_justificacion_monto_check');
        DB::statement('ALTER TABLE pag_pagos DROP CONSTRAINT IF EXISTS pag_pagos_boleta_original_check');

        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('boleta_original_verificada_por');
            $table->dropColumn(['exige_boleta_original', 'boleta_original_verificada_en', 'justificacion_monto']);
        });
    }
};
