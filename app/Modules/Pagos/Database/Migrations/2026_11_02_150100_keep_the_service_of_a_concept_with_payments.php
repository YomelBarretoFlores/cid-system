<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION pag_proteger_tipo_de_concepto() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF NEW.tipo IS DISTINCT FROM OLD.tipo AND EXISTS (SELECT 1 FROM public.pag_pagos WHERE concepto_id = OLD.id) THEN
                    RAISE EXCEPTION 'pag_conceptos: un concepto con pagos no cambia el servicio que habilita'
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER pag_conceptos_tipo_protegido BEFORE UPDATE OF tipo ON pag_conceptos
                FOR EACH ROW EXECUTE FUNCTION pag_proteger_tipo_de_concepto();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS pag_conceptos_tipo_protegido ON pag_conceptos;
            DROP FUNCTION IF EXISTS pag_proteger_tipo_de_concepto();
            SQL);
    }
};
