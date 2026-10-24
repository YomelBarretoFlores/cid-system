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
        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->dropUnique(['medio_pago', 'numero_operacion']);
        });

        // A rejected payment keeps its record but frees the operation number for a corrected registration.
        DB::statement("CREATE UNIQUE INDEX pag_pagos_operacion_unique ON pag_pagos (medio_pago, numero_operacion) WHERE estado <> 'rechazado'");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION pag_controlar_cambios() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                mutables text[] := CASE
                    WHEN OLD.estado = 'validado' AND NEW.estado = 'anulado'
                        THEN ARRAY['estado', 'anulado_por', 'anulado_en', 'motivo_anulacion', 'updated_at']
                    ELSE ARRAY['updated_at']
                END;
            BEGIN
                IF NEW.estado IS DISTINCT FROM OLD.estado AND NOT (
                    (OLD.estado = 'pendiente' AND NEW.estado IN ('validado', 'rechazado'))
                    OR (OLD.estado = 'validado' AND NEW.estado = 'anulado')
                ) THEN
                    RAISE EXCEPTION 'pag_pagos: transición no permitida de % a %', OLD.estado, NEW.estado
                        USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.estado <> 'pendiente' AND (to_jsonb(NEW) - mutables) IS DISTINCT FROM (to_jsonb(OLD) - mutables) THEN
                    RAISE EXCEPTION 'pag_pagos: un pago % no admite cambios en sus datos', OLD.estado
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER pag_pagos_ciclo_de_vida BEFORE UPDATE ON pag_pagos
                FOR EACH ROW EXECUTE FUNCTION pag_controlar_cambios();
            SQL);
    }

    public function down(): void
    {
        // Restored first so a rejected payment sharing its number with a newer one aborts the rollback intact.
        Schema::table('pag_pagos', function (Blueprint $table): void {
            $table->unique(['medio_pago', 'numero_operacion']);
        });

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS pag_pagos_ciclo_de_vida ON pag_pagos;
            DROP FUNCTION IF EXISTS pag_controlar_cambios();
            DROP INDEX IF EXISTS pag_pagos_operacion_unique;
            SQL);
    }
};
