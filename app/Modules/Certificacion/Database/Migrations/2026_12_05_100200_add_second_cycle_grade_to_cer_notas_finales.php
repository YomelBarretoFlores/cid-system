<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// An accelerated acta grades its two cycles apart; the grades it already closed with gave both cycles the same grade, so they keep it.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            ALTER TABLE cer_notas_finales
                ADD COLUMN promedio_hasta decimal(4, 2),
                ADD COLUMN estado_final_hasta varchar(12),
                ADD CONSTRAINT cer_notas_finales_estado_hasta_conocido_check CHECK (estado_final_hasta IN ('aprobado', 'desaprobado', 'nsp')),
                ADD CONSTRAINT cer_notas_finales_promedio_hasta_check CHECK (
                    (estado_final_hasta IS NULL AND promedio_hasta IS NULL)
                    OR (estado_final_hasta IS NOT NULL AND (estado_final_hasta = 'nsp') = (promedio_hasta IS NULL) AND (promedio_hasta IS NULL OR promedio_hasta >= 0))
                );

            ALTER TABLE cer_notas_finales DISABLE TRIGGER cer_notas_finales_bloqueo;
            UPDATE cer_notas_finales n SET promedio_hasta = n.promedio, estado_final_hasta = n.estado_final
            FROM cer_actas a
            WHERE a.id = n.acta_id AND a.ciclo_numero_hasta IS NOT NULL;
            ALTER TABLE cer_notas_finales ENABLE TRIGGER cer_notas_finales_bloqueo;

            CREATE OR REPLACE FUNCTION cer_controlar_notas_finales() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                acta record;
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'cer_notas_finales: una nota final no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF TG_OP = 'UPDATE' AND (NEW.acta_id, NEW.matricula_id) IS DISTINCT FROM (OLD.acta_id, OLD.matricula_id) THEN
                    RAISE EXCEPTION 'cer_notas_finales: una nota final no cambia de acta ni de estudiante' USING ERRCODE = 'check_violation';
                END IF;

                SELECT estado, ciclo_numero_hasta INTO acta FROM cer_actas WHERE id = NEW.acta_id;

                IF acta.estado = 'cerrada' THEN
                    RAISE EXCEPTION 'cer_notas_finales: el acta % está cerrada', NEW.acta_id USING ERRCODE = 'check_violation';
                END IF;

                IF (acta.ciclo_numero_hasta IS NULL) <> (NEW.estado_final_hasta IS NULL) THEN
                    RAISE EXCEPTION 'cer_notas_finales: el segundo ciclo tiene nota solo en un acta acelerada (acta %)', NEW.acta_id USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;

            COMMENT ON COLUMN cer_notas_finales.promedio_hasta IS 'Nota del segundo ciclo de un grupo acelerado; vacía si es NSP o el acta no es acelerada.';
            COMMENT ON COLUMN cer_notas_finales.estado_final_hasta IS 'Estado final del segundo ciclo de un grupo acelerado; vacío si el acta no es acelerada.';
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_controlar_notas_finales() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'cer_notas_finales: una nota final no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF TG_OP = 'UPDATE' AND (NEW.acta_id, NEW.matricula_id) IS DISTINCT FROM (OLD.acta_id, OLD.matricula_id) THEN
                    RAISE EXCEPTION 'cer_notas_finales: una nota final no cambia de acta ni de estudiante' USING ERRCODE = 'check_violation';
                END IF;

                IF (SELECT estado FROM cer_actas WHERE id = NEW.acta_id) = 'cerrada' THEN
                    RAISE EXCEPTION 'cer_notas_finales: el acta % está cerrada', NEW.acta_id USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;

            ALTER TABLE cer_notas_finales
                DROP CONSTRAINT cer_notas_finales_promedio_hasta_check,
                DROP CONSTRAINT cer_notas_finales_estado_hasta_conocido_check,
                DROP COLUMN estado_final_hasta,
                DROP COLUMN promedio_hasta;
            SQL);
    }
};
