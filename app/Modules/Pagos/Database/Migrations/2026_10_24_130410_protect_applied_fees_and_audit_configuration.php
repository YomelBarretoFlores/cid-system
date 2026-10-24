<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION pag_proteger_tarifa() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                ultimo_pago date;
            BEGIN
                SELECT max(fecha_pago) INTO ultimo_pago FROM public.pag_pagos WHERE tarifa_id = OLD.id;

                IF ultimo_pago IS NULL THEN
                    RETURN NEW;
                END IF;

                IF (NEW.concepto_id, NEW.tipo_vinculo, NEW.monto, NEW.vigente_desde)
                    IS DISTINCT FROM (OLD.concepto_id, OLD.tipo_vinculo, OLD.monto, OLD.vigente_desde) THEN
                    RAISE EXCEPTION 'pag_tarifas: una tarifa aplicada a pagos no cambia de monto, vínculo, concepto ni inicio'
                        USING ERRCODE = 'check_violation';
                END IF;

                IF NEW.vigente_hasta IS NOT NULL AND NEW.vigente_hasta < ultimo_pago THEN
                    RAISE EXCEPTION 'pag_tarifas: la tarifa no puede cerrar antes de su último pago (%)', ultimo_pago
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER pag_tarifas_protegida BEFORE UPDATE ON pag_tarifas
                FOR EACH ROW EXECUTE FUNCTION pag_proteger_tarifa();
            CREATE TRIGGER pag_tarifas_auditoria AFTER INSERT OR UPDATE OR DELETE ON pag_tarifas
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            CREATE TRIGGER pag_conceptos_auditoria AFTER INSERT OR UPDATE OR DELETE ON pag_conceptos
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS pag_conceptos_auditoria ON pag_conceptos;
            DROP TRIGGER IF EXISTS pag_tarifas_auditoria ON pag_tarifas;
            DROP TRIGGER IF EXISTS pag_tarifas_protegida ON pag_tarifas;
            DROP FUNCTION IF EXISTS pag_proteger_tarifa();
            SQL);
    }
};
