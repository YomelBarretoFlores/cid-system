<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TRUNCATE of an empty table stays allowed: test truncation of the academic offer cascades through it.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION mat_impedir_cambios_de_movimiento() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP = 'TRUNCATE' AND NOT EXISTS (SELECT 1 FROM public.mat_movimientos) THEN
                    RETURN NULL;
                END IF;

                RAISE EXCEPTION 'mat_movimientos es inmutable: un traslado o retiro no se edita ni se borra'
                    USING ERRCODE = 'check_violation';
            END;
            $$;
            CREATE TRIGGER mat_movimientos_inmutable BEFORE UPDATE OR DELETE ON mat_movimientos
                FOR EACH ROW EXECUTE FUNCTION mat_impedir_cambios_de_movimiento();
            CREATE TRIGGER mat_movimientos_sin_truncate BEFORE TRUNCATE ON mat_movimientos
                FOR EACH STATEMENT EXECUTE FUNCTION mat_impedir_cambios_de_movimiento();
            CREATE TRIGGER mat_movimientos_auditoria AFTER INSERT OR UPDATE OR DELETE ON mat_movimientos
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            CREATE TRIGGER mat_matriculas_auditoria AFTER INSERT OR UPDATE OR DELETE ON mat_matriculas
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS mat_matriculas_auditoria ON mat_matriculas;
            DROP TRIGGER IF EXISTS mat_movimientos_auditoria ON mat_movimientos;
            DROP TRIGGER IF EXISTS mat_movimientos_sin_truncate ON mat_movimientos;
            DROP TRIGGER IF EXISTS mat_movimientos_inmutable ON mat_movimientos;
            DROP FUNCTION IF EXISTS mat_impedir_cambios_de_movimiento();
            SQL);
    }
};
