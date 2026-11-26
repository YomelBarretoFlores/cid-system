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
        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->smallInteger('puntaje_maximo')->nullable();
        });

        DB::statement('ALTER TABLE mat_solicitudes_clasificacion ADD CONSTRAINT mat_solicitudes_clasificacion_puntaje_maximo_check CHECK (puntaje_maximo BETWEEN 10 AND 100 AND puntaje <= puntaje_maximo)');
        DB::statement("COMMENT ON COLUMN mat_solicitudes_clasificacion.puntaje_maximo IS 'Puntaje máximo del examen vigente cuando se registró o corrigió el resultado; vacío en resultados anteriores a este dato.'");
    }

    public function down(): void
    {
        Schema::table('mat_solicitudes_clasificacion', function (Blueprint $table): void {
            $table->dropColumn('puntaje_maximo');
        });
    }
};
