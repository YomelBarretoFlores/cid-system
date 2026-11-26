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
        foreach (['tra_tipos_tramite', 'tra_tramites'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->foreignId('concepto_id')->nullable()->constrained('pag_conceptos')->restrictOnDelete();
                $table->index('concepto_id');
            });
        }

        $this->controlarTramites("'codigo', 'persona_id', 'tipo_tramite_id', 'tipo_nombre', 'accion_final', 'tipo_concepto', 'concepto_id', 'etapas', 'datos', 'created_at'");

        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN tra_tipos_tramite.concepto_id IS 'Concepto de pago (pag_conceptos) cuya tasa exige el trámite; vacío si basta cualquier pago del tipo de concepto.';
            COMMENT ON COLUMN tra_tramites.concepto_id IS 'Concepto de pago exigido, copiado del tipo de trámite al solicitarlo.';
            SQL);
    }

    public function down(): void
    {
        $this->controlarTramites("'codigo', 'persona_id', 'tipo_tramite_id', 'tipo_nombre', 'accion_final', 'tipo_concepto', 'etapas', 'datos', 'created_at'");

        foreach (['tra_tramites', 'tra_tipos_tramite'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('concepto_id');
            });
        }
    }

    /**
     * Same function as 2026_11_02_150000, with the requested concept also fixed once requested.
     *
     * @param  literal-string  $claves
     */
    private function controlarTramites(string $claves): void
    {
        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION tra_controlar_tramites() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS \$\$
            DECLARE
                solicitud text[] := ARRAY[{$claves}];
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.estado = 'finalizado' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite finalizado no cambia' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.estado = 'cerrado' THEN
                    RAISE EXCEPTION 'tra_tramites: un trámite cerrado no cambia' USING ERRCODE = 'check_violation';
                END IF;

                IF EXISTS (SELECT 1 FROM unnest(solicitud) AS clave WHERE (to_jsonb(NEW) -> clave) IS DISTINCT FROM (to_jsonb(OLD) -> clave)) THEN
                    RAISE EXCEPTION 'tra_tramites: la solicitud de un trámite no cambia' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            \$\$;
            SQL);
    }
};
