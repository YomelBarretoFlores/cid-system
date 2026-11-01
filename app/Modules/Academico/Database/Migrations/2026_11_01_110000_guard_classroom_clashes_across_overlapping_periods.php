<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Last guard behind GuardarHorario, GuardarGrupo and VerificarCrucesDelPeriodo, checked at commit so the Actions, which run first, give the user the message.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            DROP FUNCTION IF EXISTS aca_asegurar_aula_libre(bigint);

            -- Judges only what a write can newly cause: one schedule line (p_horario), or the periods a date change
            -- starts to overlap (outside p_desde_anterior..p_hasta_anterior). A share saved before this rule stays readable and editable.
            CREATE OR REPLACE FUNCTION aca_asegurar_aula_libre(p_grupo bigint, p_horario bigint, p_desde_anterior date, p_hasta_anterior date) RETURNS void
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                g record;
                choque record;
            BEGIN
                SELECT gr.id, gr.aula_id, gr.estado, p.fecha_inicio, p.fecha_fin INTO g
                FROM public.aca_grupos gr JOIN public.aca_periodos p ON p.id = gr.periodo_id
                WHERE gr.id = p_grupo;

                IF NOT FOUND OR g.aula_id IS NULL OR g.estado = 'cancelado' THEN
                    RETURN;
                END IF;

                -- Same lock the Actions take, so two writers on one classroom run one after the other.
                PERFORM 1 FROM public.aca_aulas WHERE id = g.aula_id FOR UPDATE;

                SELECT a.codigo AS aula, otro.codigo AS grupo INTO choque
                FROM public.aca_horarios h
                JOIN public.aca_horarios h2 ON h2.dia_semana = h.dia_semana AND h2.hora_inicio < h.hora_fin AND h2.hora_fin > h.hora_inicio
                JOIN public.aca_grupos otro ON otro.id = h2.grupo_id AND otro.id <> g.id AND otro.aula_id = g.aula_id AND otro.estado <> 'cancelado'
                JOIN public.aca_periodos p2 ON p2.id = otro.periodo_id AND p2.fecha_inicio <= g.fecha_fin AND p2.fecha_fin >= g.fecha_inicio
                    AND (p_desde_anterior IS NULL OR NOT (p2.fecha_inicio <= p_hasta_anterior AND p2.fecha_fin >= p_desde_anterior))
                JOIN public.aca_aulas a ON a.id = g.aula_id
                WHERE h.grupo_id = g.id AND (p_horario IS NULL OR h.id = p_horario)
                LIMIT 1;

                IF FOUND THEN
                    RAISE EXCEPTION 'aula % ocupada por el grupo % a la misma hora', choque.aula, choque.grupo USING ERRCODE = 'check_violation';
                END IF;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_horarios_aula_libre() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.aca_asegurar_aula_libre(NEW.grupo_id, NEW.id, NULL, NULL);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_grupos_aula_libre() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                PERFORM public.aca_asegurar_aula_libre(NEW.id, NULL, NULL, NULL);
                RETURN NULL;
            END
            $$;

            CREATE OR REPLACE FUNCTION aca_periodos_aula_libre() RETURNS trigger
                LANGUAGE plpgsql
                SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                grupo bigint;
            BEGIN
                FOR grupo IN SELECT id FROM public.aca_grupos WHERE periodo_id = NEW.id ORDER BY id LOOP
                    PERFORM public.aca_asegurar_aula_libre(grupo, NULL, OLD.fecha_inicio, OLD.fecha_fin);
                END LOOP;

                RETURN NULL;
            END
            $$;

            CREATE CONSTRAINT TRIGGER aca_horarios_aula_libre AFTER INSERT OR UPDATE ON aca_horarios
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_horarios_aula_libre();
            CREATE CONSTRAINT TRIGGER aca_grupos_aula_libre AFTER UPDATE OF aula_id, periodo_id, estado ON aca_grupos
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW
                WHEN (OLD.aula_id IS DISTINCT FROM NEW.aula_id OR OLD.periodo_id IS DISTINCT FROM NEW.periodo_id OR (OLD.estado = 'cancelado' AND NEW.estado <> 'cancelado'))
                EXECUTE FUNCTION aca_grupos_aula_libre();
            CREATE CONSTRAINT TRIGGER aca_periodos_aula_libre AFTER UPDATE OF fecha_inicio, fecha_fin ON aca_periodos
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION aca_periodos_aula_libre();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS aca_periodos_aula_libre ON aca_periodos;
            DROP TRIGGER IF EXISTS aca_grupos_aula_libre ON aca_grupos;
            DROP TRIGGER IF EXISTS aca_horarios_aula_libre ON aca_horarios;
            DROP FUNCTION IF EXISTS aca_periodos_aula_libre();
            DROP FUNCTION IF EXISTS aca_grupos_aula_libre();
            DROP FUNCTION IF EXISTS aca_horarios_aula_libre();
            DROP FUNCTION IF EXISTS aca_asegurar_aula_libre(bigint, bigint, date, date);
            SQL);
    }
};
