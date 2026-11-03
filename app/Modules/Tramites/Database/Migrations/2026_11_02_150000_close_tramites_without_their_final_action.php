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
        foreach (['tra_tramites', 'tra_movimientos'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT {$tabla}_estado_check");
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_estado_check CHECK (estado IN ('pendiente_pago', 'en_proceso', 'observado', 'finalizado', 'cerrado'))");
        }

        Schema::table('tra_tramites', function (Blueprint $table): void {
            $table->timestampTz('cerrado_en')->nullable();
            $table->string('motivo_cierre', 500)->nullable();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE tra_tramites ADD CONSTRAINT tra_tramites_cierre_check CHECK (
                (estado = 'cerrado') = (cerrado_en IS NOT NULL)
                AND (estado = 'cerrado') = (motivo_cierre IS NOT NULL)
                AND (motivo_cierre IS NULL OR char_length(motivo_cierre) BETWEEN 5 AND 500)
            )
            SQL);

        $this->controlarTramites(cerradoNoCambia: true);
    }

    public function down(): void
    {
        $this->controlarTramites(cerradoNoCambia: false);

        DB::statement('ALTER TABLE tra_tramites DROP CONSTRAINT tra_tramites_cierre_check');

        Schema::table('tra_tramites', function (Blueprint $table): void {
            $table->dropColumn(['cerrado_en', 'motivo_cierre']);
        });

        foreach (['tra_tramites', 'tra_movimientos'] as $tabla) {
            DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT {$tabla}_estado_check");
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$tabla}_estado_check CHECK (estado IN ('pendiente_pago', 'en_proceso', 'observado', 'finalizado'))");
        }
    }

    // Same function as the one that created tra_tramites, plus a closed trámite being as final as a finished one.
    private function controlarTramites(bool $cerradoNoCambia): void
    {
        $cerrado = $cerradoNoCambia ? <<<'SQL'
            IF OLD.estado = 'cerrado' THEN
                RAISE EXCEPTION 'tra_tramites: un trámite cerrado no cambia' USING ERRCODE = 'check_violation';
            END IF;
            SQL : '';

        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION tra_controlar_tramites() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS \$\$
            DECLARE
                solicitud text[] := ARRAY['codigo', 'persona_id', 'tipo_tramite_id', 'tipo_nombre', 'accion_final', 'tipo_concepto', 'etapas', 'datos', 'created_at'];
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.estado = 'finalizado' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite finalizado no cambia' USING ERRCODE = 'check_violation';
                END IF;

                {$cerrado}

                IF EXISTS (SELECT 1 FROM unnest(solicitud) AS clave WHERE (to_jsonb(NEW) -> clave) IS DISTINCT FROM (to_jsonb(OLD) -> clave)) THEN
                    RAISE EXCEPTION 'tra_tramites: la solicitud de un trámite no cambia' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            \$\$;
            SQL);
    }
};
