<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Last guard behind ReglasDeCargaDocente, checked at commit: a teacher never holds two current groups whose sessions overlap in overlapping periods.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS doc_asegurar_docente_sin_cruces(bigint);

            -- Judges only what a write can newly cause, like aca_asegurar_aula_libre: one schedule line, or the periods a date change starts to overlap.
            CREATE OR REPLACE FUNCTION doc_asegurar_docente_sin_cruces(p_grupo bigint, p_horario bigint, p_desde_anterior date, p_hasta_anterior date) RETURNS void
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                g record;
                choque record;
            BEGIN
                SELECT c.docente_id, d.codigo, gr.estado, p.fecha_inicio, p.fecha_fin INTO g
                FROM public.doc_cargas_academicas c
                JOIN public.doc_docentes d ON d.id = c.docente_id
                JOIN public.aca_grupos gr ON gr.id = c.grupo_id
                JOIN public.aca_periodos p ON p.id = gr.periodo_id
                WHERE c.grupo_id = p_grupo;

                IF NOT FOUND OR g.estado IN ('cancelado', 'finalizado') THEN
                    RETURN;
                END IF;

                -- Same lock the assignment and schedule Actions take on the teacher.
                PERFORM 1 FROM public.doc_docentes WHERE id = g.docente_id FOR UPDATE;

                SELECT otro.codigo AS grupo INTO choque
                FROM public.aca_horarios h
                JOIN public.aca_horarios h2 ON h2.dia_semana = h.dia_semana AND h2.hora_inicio < h.hora_fin AND h2.hora_fin > h.hora_inicio
                JOIN public.doc_cargas_academicas c2 ON c2.grupo_id = h2.grupo_id AND c2.docente_id = g.docente_id AND c2.grupo_id <> p_grupo
                JOIN public.aca_grupos otro ON otro.id = c2.grupo_id AND otro.estado NOT IN ('cancelado', 'finalizado')
                JOIN public.aca_periodos p2 ON p2.id = otro.periodo_id AND p2.fecha_inicio <= g.fecha_fin AND p2.fecha_fin >= g.fecha_inicio
                    AND (p_desde_anterior IS NULL OR NOT (p2.fecha_inicio <= p_hasta_anterior AND p2.fecha_fin >= p_desde_anterior))
                WHERE h.grupo_id = p_grupo AND (p_horario IS NULL OR h.id = p_horario)
                LIMIT 1;

                IF FOUND THEN
                    RAISE EXCEPTION 'docente % con dos grupos a la misma hora (grupo %)', g.codigo, choque.grupo USING ERRCODE = 'check_violation';
                END IF;
            END
            $$;

            CREATE OR REPLACE FUNCTION doc_docente_sin_cruces_por_carga() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.doc_asegurar_docente_sin_cruces(NEW.grupo_id, NULL, NULL, NULL);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION doc_docente_sin_cruces_por_horario() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.doc_asegurar_docente_sin_cruces(NEW.grupo_id, NEW.id, NULL, NULL);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION doc_docente_sin_cruces_por_grupo() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.doc_asegurar_docente_sin_cruces(NEW.id, NULL, NULL, NULL);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION doc_docente_sin_cruces_por_periodo() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                grupo bigint;
            BEGIN
                FOR grupo IN SELECT id FROM public.aca_grupos WHERE periodo_id = NEW.id ORDER BY id LOOP
                    PERFORM public.doc_asegurar_docente_sin_cruces(grupo, NULL, OLD.fecha_inicio, OLD.fecha_fin);
                END LOOP;

                RETURN NULL;
            END
            $$;

            CREATE CONSTRAINT TRIGGER doc_cargas_academicas_sin_cruces AFTER INSERT ON doc_cargas_academicas
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION doc_docente_sin_cruces_por_carga();
            CREATE CONSTRAINT TRIGGER doc_cargas_academicas_cambio_sin_cruces AFTER UPDATE OF docente_id, grupo_id ON doc_cargas_academicas
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW
                WHEN (OLD.docente_id IS DISTINCT FROM NEW.docente_id OR OLD.grupo_id IS DISTINCT FROM NEW.grupo_id)
                EXECUTE FUNCTION doc_docente_sin_cruces_por_carga();
            CREATE CONSTRAINT TRIGGER doc_aca_horarios_docente_sin_cruces AFTER INSERT OR UPDATE ON aca_horarios
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION doc_docente_sin_cruces_por_horario();
            CREATE CONSTRAINT TRIGGER doc_aca_grupos_docente_sin_cruces AFTER UPDATE OF periodo_id, estado ON aca_grupos
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW
                WHEN (OLD.periodo_id IS DISTINCT FROM NEW.periodo_id OR (OLD.estado IN ('cancelado', 'finalizado') AND NEW.estado NOT IN ('cancelado', 'finalizado')))
                EXECUTE FUNCTION doc_docente_sin_cruces_por_grupo();
            CREATE CONSTRAINT TRIGGER doc_aca_periodos_docente_sin_cruces AFTER UPDATE OF fecha_inicio, fecha_fin ON aca_periodos
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION doc_docente_sin_cruces_por_periodo();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS doc_aca_periodos_docente_sin_cruces ON aca_periodos;
            DROP TRIGGER IF EXISTS doc_aca_grupos_docente_sin_cruces ON aca_grupos;
            DROP TRIGGER IF EXISTS doc_aca_horarios_docente_sin_cruces ON aca_horarios;
            DROP TRIGGER IF EXISTS doc_cargas_academicas_cambio_sin_cruces ON doc_cargas_academicas;
            DROP TRIGGER IF EXISTS doc_cargas_academicas_sin_cruces ON doc_cargas_academicas;
            DROP FUNCTION IF EXISTS doc_docente_sin_cruces_por_periodo();
            DROP FUNCTION IF EXISTS doc_docente_sin_cruces_por_grupo();
            DROP FUNCTION IF EXISTS doc_docente_sin_cruces_por_horario();
            DROP FUNCTION IF EXISTS doc_docente_sin_cruces_por_carga();
            DROP FUNCTION IF EXISTS doc_asegurar_docente_sin_cruces(bigint, bigint, date, date);
            SQL);
    }
};
