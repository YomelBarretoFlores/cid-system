<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The FUT procedures that end in an exam grade, an assigned cycle, an accreditation record or a staff resolution; the outcome is kept apart from the request, which never changes.
return new class extends Migration
{
    private const string ANTERIORES = "'ninguna', 'constancia_matricula', 'constancia_notas', 'certificado_nivel', 'traslado', 'retiro'";

    private const string NUEVAS = "'suficiencia', 'reclasificacion', 'acreditacion', 'resolucion'";

    public function up(): void
    {
        $this->acciones(self::ANTERIORES.', '.self::NUEVAS);

        Schema::table('tra_tipos_tramite', function (Blueprint $table): void {
            $table->smallInteger('meses_sin_estudiar')->nullable();
        });
        Schema::table('tra_tramites', function (Blueprint $table): void {
            $table->jsonb('resultado')->nullable();
        });

        DB::statement('ALTER TABLE tra_tipos_tramite ADD CONSTRAINT tra_tipos_tramite_meses_check CHECK (meses_sin_estudiar IS NULL OR meses_sin_estudiar BETWEEN 1 AND 24)');
        DB::statement("ALTER TABLE tra_tramites ADD CONSTRAINT tra_tramites_resultado_check CHECK (resultado IS NULL OR (jsonb_typeof(resultado) = 'object' AND estado = 'finalizado'))");
        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN tra_tipos_tramite.meses_sin_estudiar IS 'Meses que deben pasar desde el fin de la última matrícula en el idioma para pedir una reclasificación; vacío usa 2.';
            COMMENT ON COLUMN tra_tramites.resultado IS 'Resultado registrado por el personal al finalizar: nota del examen de suficiencia, ciclo de la reclasificación o comentario de la resolución.';
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tra_tramites DROP CONSTRAINT tra_tramites_resultado_check');
        DB::statement('ALTER TABLE tra_tipos_tramite DROP CONSTRAINT tra_tipos_tramite_meses_check');
        Schema::table('tra_tramites', function (Blueprint $table): void {
            $table->dropColumn('resultado');
        });
        Schema::table('tra_tipos_tramite', function (Blueprint $table): void {
            $table->dropColumn('meses_sin_estudiar');
        });

        $this->acciones(self::ANTERIORES);
    }

    /**
     * @param  literal-string  $valores
     */
    private function acciones(string $valores): void
    {
        foreach (['tra_tipos_tramite', 'tra_tramites'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT {$tabla}_accion_final_check");
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_accion_final_check CHECK (accion_final IN ({$valores}))");
        }
    }
};
