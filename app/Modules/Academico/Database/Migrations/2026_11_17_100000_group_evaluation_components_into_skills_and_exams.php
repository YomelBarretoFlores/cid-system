<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The CILN averages its skills and its exams separately, without weights; down() restores 2026_11_01_120000 with equal weights.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS aca_componentes_evaluacion_pesos ON aca_componentes_evaluacion;
            DROP TRIGGER IF EXISTS aca_esquemas_evaluacion_pesos ON aca_esquemas_evaluacion;
            DROP FUNCTION IF EXISTS aca_componentes_pesos();
            DROP FUNCTION IF EXISTS aca_esquemas_pesos();
            DROP FUNCTION IF EXISTS aca_verificar_pesos(bigint);

            ALTER TABLE aca_componentes_evaluacion ADD COLUMN grupo varchar(20);
            UPDATE aca_componentes_evaluacion SET grupo = CASE WHEN codigo IN ('parcial', 'final') THEN 'examenes' ELSE 'habilidades' END;
            ALTER TABLE aca_componentes_evaluacion ALTER COLUMN grupo SET NOT NULL;
            ALTER TABLE aca_componentes_evaluacion ADD CONSTRAINT aca_componentes_evaluacion_grupo_conocido_check CHECK (grupo IN ('habilidades', 'examenes'));
            ALTER TABLE aca_componentes_evaluacion DROP CONSTRAINT aca_componentes_evaluacion_peso_check;
            ALTER TABLE aca_componentes_evaluacion DROP COLUMN peso;

            CREATE OR REPLACE FUNCTION aca_verificar_grupos(p_esquema bigint) RETURNS void
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM public.aca_esquemas_evaluacion WHERE id = p_esquema) THEN
                    RETURN;
                END IF;

                IF NOT EXISTS (SELECT 1 FROM public.aca_componentes_evaluacion WHERE esquema_id = p_esquema AND grupo = 'habilidades')
                    OR NOT EXISTS (SELECT 1 FROM public.aca_componentes_evaluacion WHERE esquema_id = p_esquema AND grupo = 'examenes') THEN
                    RAISE EXCEPTION 'el sistema de evaluación % necesita al menos una habilidad y un examen', p_esquema USING ERRCODE = 'check_violation';
                END IF;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_esquemas_grupos() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.aca_verificar_grupos(NEW.id);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_componentes_grupos() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    PERFORM public.aca_verificar_grupos(NEW.esquema_id);
                END IF;

                IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND OLD.esquema_id <> NEW.esquema_id) THEN
                    PERFORM public.aca_verificar_grupos(OLD.esquema_id);
                END IF;

                RETURN NULL;
            END
            $$;

            CREATE CONSTRAINT TRIGGER aca_esquemas_evaluacion_grupos AFTER INSERT ON aca_esquemas_evaluacion
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_esquemas_grupos();
            CREATE CONSTRAINT TRIGGER aca_componentes_evaluacion_grupos AFTER INSERT OR UPDATE OR DELETE ON aca_componentes_evaluacion
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_componentes_grupos();

            COMMENT ON TABLE aca_componentes_evaluacion IS 'Componentes de un sistema de evaluación, cada uno habilidad o examen; cada grupo se promedia con promedio simple.';
            COMMENT ON COLUMN aca_componentes_evaluacion.grupo IS 'Grupo del componente: habilidades o examenes; la nota final promedia el promedio de cada grupo.';
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS aca_componentes_evaluacion_grupos ON aca_componentes_evaluacion;
            DROP TRIGGER IF EXISTS aca_esquemas_evaluacion_grupos ON aca_esquemas_evaluacion;
            DROP FUNCTION IF EXISTS aca_componentes_grupos();
            DROP FUNCTION IF EXISTS aca_esquemas_grupos();
            DROP FUNCTION IF EXISTS aca_verificar_grupos(bigint);

            ALTER TABLE aca_componentes_evaluacion ADD COLUMN peso decimal(5, 2);
            UPDATE aca_componentes_evaluacion c SET peso = w.peso
            FROM (
                SELECT id,
                    CASE WHEN row_number() OVER (PARTITION BY esquema_id ORDER BY orden DESC, id DESC) = 1
                        THEN 100 - (count(*) OVER (PARTITION BY esquema_id) - 1) * trunc(100.0 / count(*) OVER (PARTITION BY esquema_id), 2)
                        ELSE trunc(100.0 / count(*) OVER (PARTITION BY esquema_id), 2)
                    END AS peso
                FROM aca_componentes_evaluacion
            ) w
            WHERE c.id = w.id;
            ALTER TABLE aca_componentes_evaluacion ALTER COLUMN peso SET NOT NULL;
            ALTER TABLE aca_componentes_evaluacion ADD CONSTRAINT aca_componentes_evaluacion_peso_check CHECK (peso > 0 AND peso <= 100);
            ALTER TABLE aca_componentes_evaluacion DROP CONSTRAINT aca_componentes_evaluacion_grupo_conocido_check;
            ALTER TABLE aca_componentes_evaluacion DROP COLUMN grupo;

            CREATE OR REPLACE FUNCTION aca_verificar_pesos(p_esquema bigint) RETURNS void
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                total numeric;
                componentes integer;
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM public.aca_esquemas_evaluacion WHERE id = p_esquema) THEN
                    RETURN;
                END IF;

                SELECT coalesce(sum(peso), 0), count(*) INTO total, componentes FROM public.aca_componentes_evaluacion WHERE esquema_id = p_esquema;

                IF componentes = 0 OR total <> 100 THEN
                    RAISE EXCEPTION 'los pesos del sistema de evaluación % suman % (deben sumar 100)', p_esquema, total USING ERRCODE = 'check_violation';
                END IF;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_esquemas_pesos() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.aca_verificar_pesos(NEW.id);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_componentes_pesos() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    PERFORM public.aca_verificar_pesos(NEW.esquema_id);
                END IF;

                IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND OLD.esquema_id <> NEW.esquema_id) THEN
                    PERFORM public.aca_verificar_pesos(OLD.esquema_id);
                END IF;

                RETURN NULL;
            END
            $$;

            CREATE CONSTRAINT TRIGGER aca_esquemas_evaluacion_pesos AFTER INSERT ON aca_esquemas_evaluacion
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_esquemas_pesos();
            CREATE CONSTRAINT TRIGGER aca_componentes_evaluacion_pesos AFTER INSERT OR UPDATE OR DELETE ON aca_componentes_evaluacion
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_componentes_pesos();

            COMMENT ON TABLE aca_componentes_evaluacion IS 'Componentes de un sistema de evaluación con su peso; los pesos suman 100.';
            COMMENT ON COLUMN aca_componentes_evaluacion.peso IS 'Peso porcentual del componente.';
            SQL);
    }
};
