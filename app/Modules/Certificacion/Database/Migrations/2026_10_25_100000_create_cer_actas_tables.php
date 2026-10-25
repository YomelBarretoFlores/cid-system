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
        // The acta keeps a copy of the course, teacher and scheme it was closed with, so later changes to them never alter it.
        Schema::create('cer_actas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->unique()->constrained('aca_grupos')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('aca_periodos')->restrictOnDelete();
            $table->enum('estado', ['abierta', 'cerrada']);
            $table->string('grupo_codigo', 30);
            $table->string('idioma', 60);
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado']);
            $table->smallInteger('ciclo_numero');
            $table->string('ciclo_nombre', 60);
            $table->smallInteger('horas_lectivas');
            $table->string('periodo', 60);
            $table->foreignId('docente_id')->constrained('doc_docentes')->restrictOnDelete();
            $table->string('docente_nombre', 200);
            $table->foreignId('esquema_id')->constrained('aca_esquemas_evaluacion')->restrictOnDelete();
            $table->string('esquema_nombre', 120);
            $table->decimal('nota_maxima', 4, 2);
            $table->decimal('nota_minima_aprobatoria', 4, 2);
            $table->jsonb('componentes');
            $table->foreignId('validada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('validada_en')->nullable();
            $table->timestampsTz();

            $table->index(['periodo_id', 'estado']);
            $table->index('docente_id');
        });

        Schema::create('cer_notas_finales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('acta_id')->constrained('cer_actas')->restrictOnDelete();
            $table->foreignId('matricula_id')->constrained('mat_matriculas')->restrictOnDelete();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->string('estudiante_nombre', 200);
            $table->string('estudiante_documento', 40);
            $table->jsonb('notas');
            $table->decimal('promedio', 4, 2)->nullable();
            $table->enum('estado_final', ['aprobado', 'desaprobado', 'nsp']);
            $table->timestampsTz();

            $table->unique(['acta_id', 'matricula_id']);
            $table->index('persona_id');
            $table->index('matricula_id');
        });

        Schema::create('cer_rectificaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('acta_id')->constrained('cer_actas')->restrictOnDelete();
            $table->string('motivo', 500);
            $table->foreignId('autorizada_por')->constrained('users')->restrictOnDelete();
            $table->timestampTz('autorizada_en');
            $table->timestampTz('cerrada_en')->nullable();
            $table->timestampsTz();

            $table->index('acta_id');
        });

        DB::statement("ALTER TABLE cer_actas ADD CONSTRAINT cer_actas_validada_check CHECK (estado = 'abierta' OR (validada_por IS NOT NULL AND validada_en IS NOT NULL))");
        DB::statement('ALTER TABLE cer_actas ADD CONSTRAINT cer_actas_notas_check CHECK (nota_minima_aprobatoria > 0 AND nota_minima_aprobatoria <= nota_maxima)');
        DB::statement("ALTER TABLE cer_notas_finales ADD CONSTRAINT cer_notas_finales_promedio_check CHECK ((estado_final = 'nsp') = (promedio IS NULL) AND (promedio IS NULL OR promedio >= 0))");
        DB::statement('ALTER TABLE cer_rectificaciones ADD CONSTRAINT cer_rectificaciones_motivo_check CHECK (char_length(motivo) BETWEEN 5 AND 500)');
        DB::statement('CREATE UNIQUE INDEX cer_rectificaciones_abierta_unique ON cer_rectificaciones (acta_id) WHERE cerrada_en IS NULL');

        foreach (['cer_actas', 'cer_notas_finales', 'cer_rectificaciones'] as $tabla) {
            DB::unprepared("CREATE TRIGGER {$tabla}_auditoria AFTER INSERT OR UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION aud_registrar();");
        }

        // A closed acta only changes by reopening it for a rectification; its grades never change while it is closed, and nothing is deleted.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_controlar_acta() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'cer_actas: un acta no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF OLD.estado = 'cerrada' AND (to_jsonb(NEW) - ARRAY['estado', 'updated_at']) IS DISTINCT FROM (to_jsonb(OLD) - ARRAY['estado', 'updated_at']) THEN
                    RAISE EXCEPTION 'cer_actas: un acta cerrada no admite cambios en sus datos' USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER cer_actas_bloqueo BEFORE UPDATE OR DELETE ON cer_actas
                FOR EACH ROW EXECUTE FUNCTION cer_controlar_acta();

            CREATE OR REPLACE FUNCTION cer_controlar_notas_finales() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'cer_notas_finales: una nota final no se elimina' USING ERRCODE = 'check_violation';
                END IF;

                IF (SELECT estado FROM cer_actas WHERE id = NEW.acta_id) = 'cerrada' THEN
                    RAISE EXCEPTION 'cer_notas_finales: el acta % está cerrada', NEW.acta_id USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
            CREATE TRIGGER cer_notas_finales_bloqueo BEFORE INSERT OR UPDATE OR DELETE ON cer_notas_finales
                FOR EACH ROW EXECUTE FUNCTION cer_controlar_notas_finales();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_rectificaciones');
        Schema::dropIfExists('cer_notas_finales');
        Schema::dropIfExists('cer_actas');

        DB::unprepared('DROP FUNCTION IF EXISTS cer_controlar_notas_finales(); DROP FUNCTION IF EXISTS cer_controlar_acta();');
    }
};
