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
        // Existing rows get the defaults without an UPDATE, which the lifecycle trigger would refuse on validated rows.
        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->boolean('anterior_al_sistema')->default(false);
            $table->text('motivo_anterior')->nullable();
        });

        DB::statement('ALTER TABLE pag_pagos ALTER COLUMN recibo_path DROP NOT NULL');
        DB::statement('ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_recibo_check CHECK (recibo_path IS NOT NULL OR anterior_al_sistema)');
        DB::statement("ALTER TABLE pag_pagos ADD CONSTRAINT pag_pagos_motivo_anterior_check CHECK (anterior_al_sistema = (motivo_anterior IS NOT NULL AND btrim(motivo_anterior) <> ''))");

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN pag_pagos.recibo_path IS 'Ruta del comprobante digitalizado en el almacenamiento privado; opcional solo en un pago anterior al sistema.';
            COMMENT ON COLUMN pag_pagos.anterior_al_sistema IS 'Verdadero si el pago se hizo antes del sistema y el personal lo registró validado al matricular en ventanilla.';
            COMMENT ON COLUMN pag_pagos.motivo_anterior IS 'Motivo por el que se registró un pago anterior al sistema; obligatorio solo en ese caso.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pag_pagos DROP CONSTRAINT IF EXISTS pag_pagos_motivo_anterior_check');
        DB::statement('ALTER TABLE pag_pagos DROP CONSTRAINT IF EXISTS pag_pagos_recibo_check');
        // Aborts while a payment without a receipt exists, so the rollback cannot lose those rows.
        DB::statement('ALTER TABLE pag_pagos ALTER COLUMN recibo_path SET NOT NULL');
        DB::statement("COMMENT ON COLUMN pag_pagos.recibo_path IS 'Ruta del comprobante digitalizado en el almacenamiento privado.'");

        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->dropColumn(['anterior_al_sistema', 'motivo_anterior']);
        });
    }
};
