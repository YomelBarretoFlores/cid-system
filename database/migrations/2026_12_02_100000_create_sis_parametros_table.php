<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // column => [default, minimum, maximum, comment]; the defaults are the values the code hardcoded before.
    private const array PARAMETROS = [
        'dias_vigencia_clasificacion' => [30, 1, 365, 'Días que una solicitud de examen de clasificación sigue vigente desde que se registra.'],
        'puntaje_maximo_clasificacion' => [100, 10, 100, 'Puntaje máximo del examen de clasificación; el puntaje va de 0 a este valor.'],
        'asistencia_inicio_abre_minutos_antes' => [10, 0, 60, 'Minutos antes del inicio de la clase en que se abre el llamado de inicio.'],
        'asistencia_cierre_abre_minutos_antes_del_fin' => [15, 0, 60, 'Minutos antes del fin de la clase en que se abre el llamado de cierre.'],
        'asistencia_cierre_cierra_minutos_despues_del_fin' => [30, 0, 240, 'Minutos después del fin de la clase en que se cierra el llamado de cierre.'],
        'asistencia_confirmacion_minutos_despues_del_fin' => [120, 30, 1440, 'Minutos después del fin de la clase que tiene el delegado para confirmarla.'],
        'maximo_adjuntos_tramite' => [5, 1, 10, 'Máximo de adjuntos que puede pedir un tipo de trámite.'],
        'adjunto_maximo_mb' => [5, 1, 10, 'Tamaño máximo de cada adjunto de un trámite, en MB.'],
        'recibo_maximo_mb' => [5, 1, 10, 'Tamaño máximo del recibo de pago escaneado, en MB.'],
        'foto_maximo_mb' => [2, 1, 10, 'Tamaño máximo de la fotografía de perfil, en MB.'],
        'foto_lado_minimo_px' => [200, 100, 1000, 'Lado mínimo de la fotografía de perfil, en píxeles.'],
        'foto_lado_maximo_px' => [3000, 1000, 8000, 'Lado máximo de la fotografía de perfil, en píxeles.'],
        'umbral_pocas_vacantes' => [5, 0, 50, 'El portal público avisa pocas vacantes cuando a un grupo le quedan estas o menos.'],
    ];

    public function up(): void
    {
        Schema::create('sis_parametros', function (Blueprint $table): void {
            $table->smallInteger('id')->primary()->default(1);

            foreach (self::PARAMETROS as $columna => [$porDefecto]) {
                $table->smallInteger($columna)->default($porDefecto);
            }

            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE sis_parametros ADD CONSTRAINT sis_parametros_unica_check CHECK (id = 1)');
        DB::statement('ALTER TABLE sis_parametros ADD CONSTRAINT sis_parametros_rangos_check CHECK ('.implode(' AND ', array_map(
            fn (string $columna, array $definicion): string => "{$columna} BETWEEN {$definicion[1]} AND {$definicion[2]}",
            array_keys(self::PARAMETROS),
            self::PARAMETROS,
        )).')');
        DB::statement('ALTER TABLE sis_parametros ADD CONSTRAINT sis_parametros_confirmacion_check CHECK (asistencia_confirmacion_minutos_despues_del_fin >= asistencia_cierre_cierra_minutos_despues_del_fin)');
        DB::statement('CREATE TRIGGER sis_parametros_auditoria AFTER INSERT OR UPDATE OR DELETE ON sis_parametros FOR EACH ROW EXECUTE FUNCTION aud_registrar()');

        DB::table('sis_parametros')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);

        DB::statement("COMMENT ON TABLE sis_parametros IS 'Parámetros operativos del sistema que el personal edita en «Parámetros del sistema»; una sola fila.'");
        DB::statement("COMMENT ON COLUMN sis_parametros.id IS 'Identificador de la única fila; siempre 1.'");

        foreach (self::PARAMETROS as $columna => $definicion) {
            DB::statement("COMMENT ON COLUMN sis_parametros.{$columna} IS '{$definicion[3]}'");
        }

        DB::statement("COMMENT ON COLUMN sis_parametros.created_at IS 'Fecha y hora de creación de la fila.'");
        DB::statement("COMMENT ON COLUMN sis_parametros.updated_at IS 'Fecha y hora de la última modificación.'");
    }

    public function down(): void
    {
        Schema::dropIfExists('sis_parametros');
    }
};
