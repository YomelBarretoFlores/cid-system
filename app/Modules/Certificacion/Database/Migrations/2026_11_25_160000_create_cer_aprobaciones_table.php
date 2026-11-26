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
        // Cycles passed without an acta (placement, proficiency exam); like actas, they keep the language and cycle by name and are never changed.
        Schema::create('cer_aprobaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('persona_id')->constrained('per_personas')->restrictOnDelete();
            $table->string('idioma', 60);
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado']);
            $table->smallInteger('ciclo_numero');
            $table->string('ciclo_nombre', 60);
            $table->smallInteger('horas_lectivas')->nullable();
            $table->enum('forma', ['suficiencia', 'clasificacion']);
            $table->decimal('nota', 5, 2)->nullable();
            $table->date('fecha');
            $table->string('origen_tipo', 30);
            $table->bigInteger('origen_id');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['persona_id', 'idioma', 'ciclo_numero', 'forma']);
            $table->index('registrado_por');
            $table->index(['origen_tipo', 'origen_id']);
        });

        DB::statement('ALTER TABLE cer_aprobaciones ADD CONSTRAINT cer_aprobaciones_ciclo_numero_check CHECK (ciclo_numero > 0)');
        DB::statement('ALTER TABLE cer_aprobaciones ADD CONSTRAINT cer_aprobaciones_horas_check CHECK (horas_lectivas IS NULL OR horas_lectivas > 0)');
        DB::statement('ALTER TABLE cer_aprobaciones ADD CONSTRAINT cer_aprobaciones_nota_check CHECK (nota IS NULL OR nota >= 0)');
        DB::statement("ALTER TABLE cer_aprobaciones ADD CONSTRAINT cer_aprobaciones_clasificacion_check CHECK (forma <> 'clasificacion' OR nota IS NULL)");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION cer_impedir_cambio_de_aprobacion() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                RAISE EXCEPTION 'cer_aprobaciones: una aprobación registrada no se modifica ni se elimina' USING ERRCODE = 'check_violation';
            END;
            $$;
            CREATE TRIGGER cer_aprobaciones_bloqueo BEFORE UPDATE OR DELETE ON cer_aprobaciones
                FOR EACH ROW EXECUTE FUNCTION cer_impedir_cambio_de_aprobacion();
            CREATE TRIGGER cer_aprobaciones_sin_truncate BEFORE TRUNCATE ON cer_aprobaciones
                FOR EACH STATEMENT EXECUTE FUNCTION cer_impedir_truncate();
            CREATE TRIGGER cer_aprobaciones_auditoria AFTER INSERT OR UPDATE OR DELETE ON cer_aprobaciones
                FOR EACH ROW EXECUTE FUNCTION aud_registrar();

            COMMENT ON TABLE cer_aprobaciones IS 'Ciclos aprobados sin acta final (examen de clasificación o de suficiencia); no se modifican ni se eliminan.';
            COMMENT ON COLUMN cer_aprobaciones.id IS 'Identificador interno (llave primaria).';
            COMMENT ON COLUMN cer_aprobaciones.persona_id IS 'Estudiante que aprobó el ciclo. Referencia a `per_personas`.';
            COMMENT ON COLUMN cer_aprobaciones.idioma IS 'Nombre del idioma al registrar la aprobación, como en las actas.';
            COMMENT ON COLUMN cer_aprobaciones.nivel IS 'Nivel curricular del ciclo aprobado.';
            COMMENT ON COLUMN cer_aprobaciones.ciclo_numero IS 'Número del ciclo aprobado en el plan de estudios del idioma.';
            COMMENT ON COLUMN cer_aprobaciones.ciclo_nombre IS 'Nombre del ciclo al registrar la aprobación.';
            COMMENT ON COLUMN cer_aprobaciones.horas_lectivas IS 'Horas lectivas reconocidas; vacío cuando el ciclo no se cursó (clasificación).';
            COMMENT ON COLUMN cer_aprobaciones.forma IS 'Forma de aprobación del ciclo.';
            COMMENT ON COLUMN cer_aprobaciones.nota IS 'Nota obtenida en el examen; vacía en la clasificación, que no califica los ciclos que salta.';
            COMMENT ON COLUMN cer_aprobaciones.fecha IS 'Fecha del examen (hora de Lima), de la que salen el año y el mes de la constancia.';
            COMMENT ON COLUMN cer_aprobaciones.origen_tipo IS 'Registro que originó la aprobación: solicitud_clasificacion o tramite.';
            COMMENT ON COLUMN cer_aprobaciones.origen_id IS 'Identificador del registro que originó la aprobación.';
            COMMENT ON COLUMN cer_aprobaciones.registrado_por IS 'Usuario que registró el resultado del examen.';
            COMMENT ON COLUMN cer_aprobaciones.created_at IS 'Fecha y hora de creación del registro.';
            COMMENT ON COLUMN cer_aprobaciones.updated_at IS 'Fecha y hora de la última modificación del registro.';
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('cer_aprobaciones');

        DB::unprepared('DROP FUNCTION IF EXISTS cer_impedir_cambio_de_aprobacion();');
    }
};
