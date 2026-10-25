<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array TABLAS = ['cer_actas', 'cer_notas_finales', 'cer_rectificaciones', 'cer_documentos'];

    public function up(): void
    {
        // Row-level triggers do not fire on TRUNCATE; an empty table may still be truncated, as the test truncation cascades through it.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_impedir_truncate() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                con_filas boolean;
            BEGIN
                EXECUTE format('SELECT EXISTS (SELECT 1 FROM public.%I)', TG_TABLE_NAME) INTO con_filas;

                IF con_filas THEN
                    RAISE EXCEPTION '%: las actas y los documentos emitidos no se borran', TG_TABLE_NAME USING ERRCODE = 'check_violation';
                END IF;

                RETURN NULL;
            END;
            $$;
            SQL);

        foreach (self::TABLAS as $tabla) {
            DB::unprepared("CREATE TRIGGER {$tabla}_sin_truncate BEFORE TRUNCATE ON {$tabla} FOR EACH STATEMENT EXECUTE FUNCTION cer_impedir_truncate();");
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$tabla}_sin_truncate ON {$tabla};");
        }

        DB::unprepared('DROP FUNCTION IF EXISTS cer_impedir_truncate();');
    }
};
