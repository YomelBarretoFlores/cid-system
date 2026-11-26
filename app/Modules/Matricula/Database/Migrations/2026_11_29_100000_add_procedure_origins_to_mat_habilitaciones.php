<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// A passed proficiency exam and a reclassification trámite also set the enabled cycle; their own origins keep placement corrections away from them.
return new class extends Migration
{
    public function up(): void
    {
        $this->origenes("'clasificacion', 'aprobacion', 'ajuste_administrativo', 'suficiencia', 'reclasificacion'");

        DB::statement("COMMENT ON COLUMN mat_habilitaciones.origen IS 'Origen de la habilitación: clasificación, aprobación del ciclo anterior, ajuste administrativo, examen de suficiencia o reclasificación.'");
    }

    public function down(): void
    {
        $this->origenes("'clasificacion', 'aprobacion', 'ajuste_administrativo'");

        DB::statement("COMMENT ON COLUMN mat_habilitaciones.origen IS 'Origen de la habilitación: clasificación, aprobación del ciclo anterior o ajuste administrativo.'");
    }

    /**
     * @param  literal-string  $valores
     */
    private function origenes(string $valores): void
    {
        DB::statement('ALTER TABLE mat_habilitaciones DROP CONSTRAINT mat_habilitaciones_origen_check');
        DB::statement("ALTER TABLE mat_habilitaciones ADD CONSTRAINT mat_habilitaciones_origen_check CHECK (origen IN ({$valores}))");
    }
};
