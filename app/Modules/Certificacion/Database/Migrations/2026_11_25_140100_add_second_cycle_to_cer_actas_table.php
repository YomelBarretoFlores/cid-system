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
        // An accelerated group closes one acta for two cycles: horas_lectivas keeps the total and horas_lectivas_hasta the second cycle's share.
        Schema::table('cer_actas', function (Blueprint $table): void {
            $table->smallInteger('ciclo_numero_hasta')->nullable();
            $table->string('ciclo_nombre_hasta', 60)->nullable();
            $table->smallInteger('horas_lectivas_hasta')->nullable();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE cer_actas ADD CONSTRAINT cer_actas_ciclo_hasta_check CHECK (
                (ciclo_numero_hasta IS NULL AND ciclo_nombre_hasta IS NULL AND horas_lectivas_hasta IS NULL)
                OR (ciclo_numero_hasta = ciclo_numero + 1 AND ciclo_nombre_hasta IS NOT NULL AND horas_lectivas_hasta > 0 AND horas_lectivas_hasta < horas_lectivas)
            )
            SQL);

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN cer_actas.ciclo_numero_hasta IS 'Número del segundo ciclo cuando el acta es de un grupo acelerado; vacío en un grupo regular.';
            COMMENT ON COLUMN cer_actas.ciclo_nombre_hasta IS 'Nombre del segundo ciclo al cerrar el acta de un grupo acelerado.';
            COMMENT ON COLUMN cer_actas.horas_lectivas_hasta IS 'Horas lectivas del segundo ciclo de un grupo acelerado, incluidas en horas_lectivas.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cer_actas DROP CONSTRAINT IF EXISTS cer_actas_ciclo_hasta_check');

        Schema::table('cer_actas', function (Blueprint $table): void {
            $table->dropColumn(['ciclo_numero_hasta', 'ciclo_nombre_hasta', 'horas_lectivas_hasta']);
        });
    }
};
