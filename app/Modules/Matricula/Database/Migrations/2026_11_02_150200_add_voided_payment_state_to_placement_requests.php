<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE mat_solicitudes_clasificacion DROP CONSTRAINT mat_solicitudes_clasificacion_estado_check');
        DB::statement("ALTER TABLE mat_solicitudes_clasificacion ADD CONSTRAINT mat_solicitudes_clasificacion_estado_check CHECK (estado IN ('pendiente_evaluacion', 'evaluado', 'expirado', 'pago_anulado'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE mat_solicitudes_clasificacion DROP CONSTRAINT mat_solicitudes_clasificacion_estado_check');
        DB::statement("ALTER TABLE mat_solicitudes_clasificacion ADD CONSTRAINT mat_solicitudes_clasificacion_estado_check CHECK (estado IN ('pendiente_evaluacion', 'evaluado', 'expirado'))");
    }
};
