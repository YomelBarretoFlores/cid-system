<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Checked at commit, after GuardarEsquemaDeEvaluacion writes a scheme and all its components, which gives the user the message.
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
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

            CREATE TRIGGER aca_esquemas_evaluacion_auditoria AFTER INSERT OR UPDATE OR DELETE ON aca_esquemas_evaluacion
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            CREATE TRIGGER aca_componentes_evaluacion_auditoria AFTER INSERT OR UPDATE OR DELETE ON aca_componentes_evaluacion
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS aca_componentes_evaluacion_auditoria ON aca_componentes_evaluacion;
            DROP TRIGGER IF EXISTS aca_esquemas_evaluacion_auditoria ON aca_esquemas_evaluacion;
            DROP TRIGGER IF EXISTS aca_componentes_evaluacion_pesos ON aca_componentes_evaluacion;
            DROP TRIGGER IF EXISTS aca_esquemas_evaluacion_pesos ON aca_esquemas_evaluacion;
            DROP FUNCTION IF EXISTS aca_componentes_pesos();
            DROP FUNCTION IF EXISTS aca_esquemas_pesos();
            DROP FUNCTION IF EXISTS aca_verificar_pesos(bigint);
            SQL);
    }
};
