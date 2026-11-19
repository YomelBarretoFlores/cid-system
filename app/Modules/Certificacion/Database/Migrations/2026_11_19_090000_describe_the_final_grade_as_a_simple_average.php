<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("COMMENT ON COLUMN cer_notas_finales.promedio IS 'Nota final: promedio simple del promedio de habilidades y del promedio de exámenes, redondeado a entero; nulo si no se presentó.'");
    }

    public function down(): void
    {
        DB::statement("COMMENT ON COLUMN cer_notas_finales.promedio IS 'Promedio ponderado; nulo si no se presentó.'");
    }
};
