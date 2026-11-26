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
        // A placement corrected to a lower cycle voids the approvals it no longer grants; a voided row frees its cycle for a later approval.
        Schema::table('cer_aprobaciones', function (Blueprint $table): void {
            $table->timestampTz('anulada_en')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('motivo_anulacion', 300)->nullable();

            $table->index('anulada_por');
            $table->index(['persona_id', 'idioma']);
            $table->dropUnique(['persona_id', 'idioma', 'ciclo_numero', 'forma']);
        });

        DB::statement('ALTER TABLE cer_aprobaciones ADD CONSTRAINT cer_aprobaciones_anulacion_check CHECK ((anulada_en IS NULL) = (anulada_por IS NULL) AND (anulada_en IS NULL) = (motivo_anulacion IS NULL))');
        DB::statement('CREATE UNIQUE INDEX cer_aprobaciones_vigente_unique ON cer_aprobaciones (persona_id, idioma, ciclo_numero, forma) WHERE anulada_en IS NULL');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_impedir_cambio_de_aprobacion() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                anulacion text[] := ARRAY['anulada_en', 'anulada_por', 'motivo_anulacion', 'updated_at'];
            BEGIN
                IF TG_OP = 'UPDATE' AND OLD.anulada_en IS NULL AND NEW.anulada_en IS NOT NULL
                    AND (to_jsonb(NEW) - anulacion) IS NOT DISTINCT FROM (to_jsonb(OLD) - anulacion) THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'cer_aprobaciones: una aprobación registrada solo admite su anulación, una vez' USING ERRCODE = 'check_violation';
            END;
            $$;

            COMMENT ON TABLE cer_aprobaciones IS 'Ciclos aprobados sin acta final (examen de clasificación o de suficiencia); no se eliminan y solo se anulan una vez.';
            COMMENT ON COLUMN cer_aprobaciones.anulada_en IS 'Fecha y hora de anulación; vacía mientras la aprobación está vigente.';
            COMMENT ON COLUMN cer_aprobaciones.anulada_por IS 'Usuario que anuló la aprobación.';
            COMMENT ON COLUMN cer_aprobaciones.motivo_anulacion IS 'Motivo de la anulación.';
            SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_impedir_cambio_de_aprobacion() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                RAISE EXCEPTION 'cer_aprobaciones: una aprobación registrada no se modifica ni se elimina' USING ERRCODE = 'check_violation';
            END;
            $$;
            COMMENT ON TABLE cer_aprobaciones IS 'Ciclos aprobados sin acta final (examen de clasificación o de suficiencia); no se modifican ni se eliminan.';
            SQL);

        DB::statement('DROP INDEX IF EXISTS cer_aprobaciones_vigente_unique');
        DB::statement('ALTER TABLE cer_aprobaciones DROP CONSTRAINT IF EXISTS cer_aprobaciones_anulacion_check');

        Schema::table('cer_aprobaciones', function (Blueprint $table): void {
            $table->dropIndex(['persona_id', 'idioma']);
            $table->dropConstrainedForeignId('anulada_por');
            $table->dropColumn(['anulada_en', 'motivo_anulacion']);
            $table->unique(['persona_id', 'idioma', 'ciclo_numero', 'forma']);
        });
    }
};
