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
        // A valid document whose acta was rectified stays valid but is flagged for staff; voiding it is still a manual decision.
        Schema::table('cer_documentos', function (Blueprint $table): void {
            $table->timestampTz('revisar_desde')->nullable();
            $table->string('motivo_revision', 250)->nullable();
        });

        DB::statement('ALTER TABLE cer_documentos ADD CONSTRAINT cer_documentos_revision_check CHECK ((revisar_desde IS NULL) = (motivo_revision IS NULL))');
        DB::statement('CREATE INDEX cer_documentos_revisar_desde_index ON cer_documentos (revisar_desde) WHERE revisar_desde IS NOT NULL AND anulado_en IS NULL');

        // The grades of the acta when the rectification was authorized, to know whose result changed when it closes again.
        Schema::table('cer_rectificaciones', function (Blueprint $table): void {
            $table->jsonb('notas_previas')->nullable();
        });

        $this->bloqueo("ARRAY['anulado_por', 'anulado_en', 'motivo_anulacion', 'revisar_desde', 'motivo_revision', 'updated_at']");
    }

    public function down(): void
    {
        $this->bloqueo("ARRAY['anulado_por', 'anulado_en', 'motivo_anulacion', 'updated_at']");
        DB::statement('DROP INDEX IF EXISTS cer_documentos_revisar_desde_index');
        DB::statement('ALTER TABLE cer_documentos DROP CONSTRAINT IF EXISTS cer_documentos_revision_check');

        Schema::table('cer_documentos', function (Blueprint $table): void {
            $table->dropColumn(['revisar_desde', 'motivo_revision']);
        });

        Schema::table('cer_rectificaciones', function (Blueprint $table): void {
            $table->dropColumn('notas_previas');
        });
    }

    /**
     * @param  literal-string  $columnas  SQL array of the columns a valid document may still change
     */
    private function bloqueo(string $columnas): void
    {
        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION cer_controlar_documentos() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS \$\$
            DECLARE
                editables text[] := {$columnas};
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'cer_documentos: un documento emitido no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.anulado_en IS NOT NULL OR (to_jsonb(NEW) - editables) IS DISTINCT FROM (to_jsonb(OLD) - editables) THEN
                    RAISE EXCEPTION 'cer_documentos: un documento emitido solo admite su anulación' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            \$\$;
            SQL);
    }
};
