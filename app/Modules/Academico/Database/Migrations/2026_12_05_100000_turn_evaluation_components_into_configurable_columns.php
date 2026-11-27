<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Each scheme keeps its grades: skills and exams become entered columns, and the two averages and the final grade become calculated columns.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS aca_componentes_evaluacion_grupos ON aca_componentes_evaluacion;
            DROP TRIGGER IF EXISTS aca_esquemas_evaluacion_grupos ON aca_esquemas_evaluacion;
            DROP FUNCTION IF EXISTS aca_componentes_grupos();
            DROP FUNCTION IF EXISTS aca_esquemas_grupos();
            DROP FUNCTION IF EXISTS aca_verificar_grupos(bigint);

            ALTER TABLE aca_componentes_evaluacion
                ADD COLUMN tipo varchar(10) NOT NULL DEFAULT 'ingresada',
                ADD COLUMN calculo varchar(10),
                ADD COLUMN redondeo varchar(10) NOT NULL DEFAULT 'ninguno',
                ADD COLUMN fuentes jsonb NOT NULL DEFAULT '[]'::jsonb,
                ADD COLUMN ciclo smallint;

            INSERT INTO aca_componentes_evaluacion (esquema_id, codigo, nombre, grupo, orden, tipo, calculo, redondeo, fuentes, ciclo, created_at, updated_at)
            SELECT c.esquema_id, v.codigo, v.nombre, c.grupo, m.orden + v.desplazamiento, 'calculada', 'promedio', 'ninguno',
                jsonb_agg(jsonb_build_object('codigo', c.codigo, 'peso', 1) ORDER BY c.orden, c.id), NULL, now(), now()
            FROM aca_componentes_evaluacion c
            JOIN (SELECT esquema_id, max(orden) AS orden FROM aca_componentes_evaluacion GROUP BY esquema_id) m ON m.esquema_id = c.esquema_id
            JOIN (VALUES ('habilidades', 'promedio_habilidades', 'Promedio de habilidades', 1), ('examenes', 'promedio_examenes', 'Promedio de exámenes', 2))
                AS v(grupo, codigo, nombre, desplazamiento) ON v.grupo = c.grupo
            GROUP BY c.esquema_id, c.grupo, v.codigo, v.nombre, m.orden, v.desplazamiento;

            INSERT INTO aca_componentes_evaluacion (esquema_id, codigo, nombre, grupo, orden, tipo, calculo, redondeo, fuentes, ciclo, created_at, updated_at)
            SELECT esquema_id, 'nota_final', 'Nota final', 'examenes', max(orden) + 1, 'calculada', 'promedio', 'entero',
                '[{"codigo": "promedio_habilidades", "peso": 1}, {"codigo": "promedio_examenes", "peso": 1}]'::jsonb, 1, now(), now()
            FROM aca_componentes_evaluacion
            GROUP BY esquema_id;

            ALTER TABLE aca_componentes_evaluacion DROP CONSTRAINT aca_componentes_evaluacion_grupo_conocido_check;
            ALTER TABLE aca_componentes_evaluacion DROP COLUMN grupo;
            ALTER TABLE aca_componentes_evaluacion ALTER COLUMN tipo DROP DEFAULT;

            ALTER TABLE aca_componentes_evaluacion
                ADD CONSTRAINT aca_componentes_evaluacion_tipo_conocido_check CHECK (tipo IN ('ingresada', 'calculada')),
                ADD CONSTRAINT aca_componentes_evaluacion_calculo_conocido_check CHECK (calculo IN ('promedio', 'ponderado')),
                ADD CONSTRAINT aca_componentes_evaluacion_redondeo_conocido_check CHECK (redondeo IN ('ninguno', 'entero')),
                ADD CONSTRAINT aca_componentes_evaluacion_ciclo_conocido_check CHECK (ciclo IN (1, 2)),
                ADD CONSTRAINT aca_componentes_evaluacion_fuentes_lista_check CHECK (jsonb_typeof(fuentes) = 'array'),
                ADD CONSTRAINT aca_componentes_evaluacion_calculada_check CHECK (
                    (tipo = 'ingresada' AND calculo IS NULL AND redondeo = 'ninguno' AND fuentes = '[]'::jsonb)
                    OR (tipo = 'calculada' AND calculo IS NOT NULL AND jsonb_array_length(fuentes) > 0)
                );
            CREATE UNIQUE INDEX aca_componentes_evaluacion_ciclo_unique ON aca_componentes_evaluacion (esquema_id, ciclo) WHERE ciclo IS NOT NULL;

            CREATE OR REPLACE FUNCTION aca_verificar_componentes(p_esquema bigint) RETURNS void
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM public.aca_esquemas_evaluacion WHERE id = p_esquema) THEN
                    RETURN;
                END IF;

                IF NOT EXISTS (SELECT 1 FROM public.aca_componentes_evaluacion WHERE esquema_id = p_esquema AND tipo = 'ingresada')
                    OR NOT EXISTS (SELECT 1 FROM public.aca_componentes_evaluacion WHERE esquema_id = p_esquema AND ciclo = 1) THEN
                    RAISE EXCEPTION 'el sistema de evaluación % necesita una columna que ingrese el docente y la nota del ciclo', p_esquema USING ERRCODE = 'check_violation';
                END IF;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_esquemas_componentes() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.aca_verificar_componentes(NEW.id);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_componentes_completos() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP IN ('INSERT', 'UPDATE') THEN
                    PERFORM public.aca_verificar_componentes(NEW.esquema_id);
                END IF;

                IF TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND OLD.esquema_id <> NEW.esquema_id) THEN
                    PERFORM public.aca_verificar_componentes(OLD.esquema_id);
                END IF;

                RETURN NULL;
            END
            $$;

            CREATE CONSTRAINT TRIGGER aca_esquemas_evaluacion_componentes AFTER INSERT ON aca_esquemas_evaluacion
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_esquemas_componentes();
            CREATE CONSTRAINT TRIGGER aca_componentes_evaluacion_completos AFTER INSERT OR UPDATE OR DELETE ON aca_componentes_evaluacion
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_componentes_completos();

            ALTER TABLE aca_esquemas_evaluacion
                ADD COLUMN programa_id bigint REFERENCES aca_programas (id),
                ADD COLUMN regimen varchar(10),
                ADD CONSTRAINT aca_esquemas_evaluacion_regimen_conocido_check CHECK (regimen IN ('regular', 'acelerado'));
            CREATE INDEX aca_esquemas_evaluacion_programa_id_index ON aca_esquemas_evaluacion (programa_id);

            COMMENT ON TABLE aca_componentes_evaluacion IS 'Columnas de un sistema de evaluación: las que ingresa el docente y las calculadas a partir de otras columnas.';
            COMMENT ON COLUMN aca_componentes_evaluacion.tipo IS 'ingresada: la nota la registra el docente; calculada: se obtiene de las columnas de fuentes.';
            COMMENT ON COLUMN aca_componentes_evaluacion.calculo IS 'Solo columnas calculadas: promedio (simple) o ponderado (con el peso de cada fuente).';
            COMMENT ON COLUMN aca_componentes_evaluacion.redondeo IS 'ninguno: se muestra con dos decimales y se usa sin redondear; entero: redondeo al entero, 0.5 hacia arriba.';
            COMMENT ON COLUMN aca_componentes_evaluacion.fuentes IS 'Columnas anteriores que usa una columna calculada: lista de {codigo, peso}; el peso solo cuenta en el promedio ponderado.';
            COMMENT ON COLUMN aca_componentes_evaluacion.ciclo IS 'Si la columna es la nota del ciclo: 1 para el ciclo del grupo, 2 para el segundo ciclo de un grupo acelerado.';
            COMMENT ON COLUMN aca_esquemas_evaluacion.programa_id IS 'Programa al que se aplica el sistema; vacío para todos los programas.';
            COMMENT ON COLUMN aca_esquemas_evaluacion.regimen IS 'Régimen al que se aplica: regular o acelerado; vacío para ambos.';
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP INDEX IF EXISTS aca_esquemas_evaluacion_programa_id_index;
            ALTER TABLE aca_esquemas_evaluacion DROP CONSTRAINT aca_esquemas_evaluacion_regimen_conocido_check;
            ALTER TABLE aca_esquemas_evaluacion DROP COLUMN regimen, DROP COLUMN programa_id;

            DROP TRIGGER IF EXISTS aca_componentes_evaluacion_completos ON aca_componentes_evaluacion;
            DROP TRIGGER IF EXISTS aca_esquemas_evaluacion_componentes ON aca_esquemas_evaluacion;
            DROP FUNCTION IF EXISTS aca_componentes_completos();
            DROP FUNCTION IF EXISTS aca_esquemas_componentes();
            DROP FUNCTION IF EXISTS aca_verificar_componentes(bigint);

            ALTER TABLE aca_componentes_evaluacion ADD COLUMN grupo varchar(20);
            UPDATE aca_componentes_evaluacion c SET grupo = CASE WHEN EXISTS (
                SELECT 1 FROM aca_componentes_evaluacion e
                WHERE e.esquema_id = c.esquema_id AND e.codigo = 'promedio_examenes' AND e.fuentes @> jsonb_build_array(jsonb_build_object('codigo', c.codigo))
            ) OR c.codigo IN ('parcial', 'final') THEN 'examenes' ELSE 'habilidades' END;
            DELETE FROM aca_componentes_evaluacion WHERE tipo = 'calculada';

            DROP INDEX IF EXISTS aca_componentes_evaluacion_ciclo_unique;
            ALTER TABLE aca_componentes_evaluacion
                DROP CONSTRAINT aca_componentes_evaluacion_calculada_check,
                DROP CONSTRAINT aca_componentes_evaluacion_fuentes_lista_check,
                DROP CONSTRAINT aca_componentes_evaluacion_ciclo_conocido_check,
                DROP CONSTRAINT aca_componentes_evaluacion_redondeo_conocido_check,
                DROP CONSTRAINT aca_componentes_evaluacion_calculo_conocido_check,
                DROP CONSTRAINT aca_componentes_evaluacion_tipo_conocido_check,
                DROP COLUMN ciclo, DROP COLUMN fuentes, DROP COLUMN redondeo, DROP COLUMN calculo, DROP COLUMN tipo;
            ALTER TABLE aca_componentes_evaluacion ALTER COLUMN grupo SET NOT NULL;
            ALTER TABLE aca_componentes_evaluacion ADD CONSTRAINT aca_componentes_evaluacion_grupo_conocido_check CHECK (grupo IN ('habilidades', 'examenes'));

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
};
