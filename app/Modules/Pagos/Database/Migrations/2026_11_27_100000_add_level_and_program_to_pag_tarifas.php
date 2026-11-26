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
        Schema::table('pag_tarifas', function (Blueprint $table): void {
            $table->string('nivel', 12)->nullable();
            $table->foreignId('programa_id')->nullable()->constrained('aca_programas')->restrictOnDelete();
            $table->index('programa_id');
        });

        DB::statement("ALTER TABLE pag_tarifas ADD CONSTRAINT pag_tarifas_nivel_valido_check CHECK (nivel IS NULL OR nivel IN ('basico', 'intermedio', 'avanzado'))");
        DB::statement('ALTER TABLE pag_tarifas DROP CONSTRAINT pag_tarifas_sin_superposicion');
        DB::statement(<<<'SQL'
            ALTER TABLE pag_tarifas ADD CONSTRAINT pag_tarifas_sin_superposicion EXCLUDE USING gist (
                concepto_id WITH =,
                tipo_vinculo WITH =,
                (coalesce(nivel, '')) WITH =,
                (coalesce(programa_id, 0)) WITH =,
                daterange(vigente_desde, vigente_hasta, '[]') WITH &&
            )
            SQL);

        $this->protegerTarifa("'concepto_id', 'tipo_vinculo', 'monto', 'vigente_desde', 'nivel', 'programa_id'", 'monto, vínculo, concepto, nivel, programa ni inicio');

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN pag_tarifas.nivel IS 'Nivel al que se aplica la tarifa (basico, intermedio, avanzado); vacío si vale para todos los niveles.';
            COMMENT ON COLUMN pag_tarifas.programa_id IS 'Programa al que se aplica la tarifa (aca_programas); vacío si vale para todos los programas.';
            SQL);
    }

    public function down(): void
    {
        $this->protegerTarifa("'concepto_id', 'tipo_vinculo', 'monto', 'vigente_desde'", 'monto, vínculo, concepto ni inicio');

        DB::statement('ALTER TABLE pag_tarifas DROP CONSTRAINT pag_tarifas_sin_superposicion');
        DB::statement('ALTER TABLE pag_tarifas DROP CONSTRAINT pag_tarifas_nivel_valido_check');

        Schema::table('pag_tarifas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('programa_id');
            $table->dropColumn('nivel');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE pag_tarifas ADD CONSTRAINT pag_tarifas_sin_superposicion EXCLUDE USING gist (
                concepto_id WITH =,
                tipo_vinculo WITH =,
                daterange(vigente_desde, vigente_hasta, '[]') WITH &&
            )
            SQL);
    }

    /**
     * Same function as 2026_10_24_130410, comparing the frozen columns by name.
     *
     * @param  literal-string  $congeladas
     * @param  literal-string  $mensaje
     */
    private function protegerTarifa(string $congeladas, string $mensaje): void
    {
        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION pag_proteger_tarifa() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS \$\$
            DECLARE
                ultimo_pago date;
            BEGIN
                SELECT max(fecha_pago) INTO ultimo_pago FROM public.pag_pagos WHERE tarifa_id = OLD.id;

                IF ultimo_pago IS NULL THEN
                    RETURN NEW;
                END IF;

                IF EXISTS (SELECT 1 FROM unnest(ARRAY[{$congeladas}]) AS clave WHERE (to_jsonb(NEW) -> clave) IS DISTINCT FROM (to_jsonb(OLD) -> clave)) THEN
                    RAISE EXCEPTION 'pag_tarifas: una tarifa aplicada a pagos no cambia de {$mensaje}'
                        USING ERRCODE = 'check_violation';
                END IF;

                IF NEW.vigente_hasta IS NOT NULL AND NEW.vigente_hasta < ultimo_pago THEN
                    RAISE EXCEPTION 'pag_tarifas: la tarifa no puede cerrar antes de su último pago (%)', ultimo_pago
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            \$\$;
            SQL);
    }
};
