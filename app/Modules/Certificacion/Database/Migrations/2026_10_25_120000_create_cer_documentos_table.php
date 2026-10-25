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
        // Each document keeps a copy of what it certified, so the public verification never depends on data changed later.
        Schema::create('cer_documentos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->enum('tipo', ['constancia_matricula', 'constancia_notas', 'certificado_nivel']);
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('mat_matriculas')->restrictOnDelete();
            $table->string('estudiante_nombre', 200);
            $table->string('estudiante_documento', 40);
            $table->string('idioma', 60);
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado'])->nullable();
            $table->smallInteger('horas_lectivas')->nullable();
            $table->decimal('nota_final', 4, 2)->nullable();
            $table->jsonb('detalle');
            $table->foreignId('emitido_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('emitido_en');
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('anulado_en')->nullable();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->timestampsTz();

            $table->index('persona_id');
            $table->index('matricula_id');
            $table->index('emitido_por');
            $table->index('anulado_por');
            $table->index(['tipo', 'emitido_en']);
        });

        DB::statement('ALTER TABLE cer_documentos ADD CONSTRAINT cer_documentos_anulacion_check CHECK ((anulado_en IS NULL) = (anulado_por IS NULL) AND (anulado_en IS NULL) = (motivo_anulacion IS NULL) AND (motivo_anulacion IS NULL OR char_length(motivo_anulacion) BETWEEN 5 AND 500))');
        DB::statement("ALTER TABLE cer_documentos ADD CONSTRAINT cer_documentos_certificado_check CHECK (tipo <> 'certificado_nivel' OR (nivel IS NOT NULL AND horas_lectivas IS NOT NULL AND nota_final IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX cer_documentos_certificado_vigente_unique ON cer_documentos (persona_id, idioma, nivel) WHERE tipo = 'certificado_nivel' AND anulado_en IS NULL");
        DB::unprepared('CREATE TRIGGER cer_documentos_auditoria AFTER INSERT OR UPDATE OR DELETE ON cer_documentos FOR EACH ROW EXECUTE FUNCTION aud_registrar();');

        // An issued document is never edited or deleted; it can only be voided once.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_controlar_documentos() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            DECLARE
                anulacion text[] := ARRAY['anulado_por', 'anulado_en', 'motivo_anulacion', 'updated_at'];
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'cer_documentos: un documento emitido no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.anulado_en IS NOT NULL OR (to_jsonb(NEW) - anulacion) IS DISTINCT FROM (to_jsonb(OLD) - anulacion) THEN
                    RAISE EXCEPTION 'cer_documentos: un documento emitido solo admite su anulación' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER cer_documentos_bloqueo BEFORE UPDATE OR DELETE ON cer_documentos
                FOR EACH ROW EXECUTE FUNCTION cer_controlar_documentos();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_documentos');

        DB::unprepared('DROP FUNCTION IF EXISTS cer_controlar_documentos();');
    }
};
