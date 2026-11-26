<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Each type now says which FUT fields, prior trámite, programs and attachments it asks; existing types keep exactly what their final action asked.
return new class extends Migration
{
    private const array CAMPOS_POR_ACCION = [
        'constancia_matricula' => ['matricula'],
        'constancia_notas' => ['idioma', 'nivel'],
        'certificado_nivel' => ['idioma', 'nivel'],
        'traslado' => ['matricula', 'motivo'],
        'retiro' => ['matricula', 'motivo'],
    ];

    private const string REQUISITO_ANTERIOR = 'Todos los ciclos del nivel aprobados en actas cerradas y el pago de la tasa.';

    private const string REQUISITO_ACTUAL = 'La constancia de notas del mismo idioma y nivel tramitada en el sistema, todos los ciclos del nivel aprobados en actas cerradas y el pago de la tasa.';

    public function up(): void
    {
        Schema::table('tra_tipos_tramite', function (Blueprint $table): void {
            $table->jsonb('campos')->default('[]');
            $table->jsonb('adjuntos')->default('[]');
            $table->foreignId('requiere_tipo_previo_id')->nullable()->constrained('tra_tipos_tramite')->restrictOnDelete();
            $table->index('requiere_tipo_previo_id');
        });

        DB::statement("ALTER TABLE tra_tipos_tramite ADD CONSTRAINT tra_tipos_tramite_fut_check CHECK (jsonb_typeof(campos) = 'array' AND jsonb_typeof(adjuntos) = 'array' AND jsonb_array_length(adjuntos) <= 5 AND requiere_tipo_previo_id IS DISTINCT FROM id)");

        foreach (self::CAMPOS_POR_ACCION as $accion => $campos) {
            DB::table('tra_tipos_tramite')->where('accion_final', $accion)->update([
                'campos' => json_encode(array_map(fn (string $clave): array => ['clave' => $clave, 'obligatorio' => true], $campos), JSON_THROW_ON_ERROR),
            ]);
        }

        $constancia = DB::table('tra_tipos_tramite')->where('codigo', 'CONSTANCIA-NOTAS')->value('id');

        if ($constancia !== null) {
            DB::table('tra_tipos_tramite')->where('codigo', 'CERTIFICADO-NIVEL')->update(['requiere_tipo_previo_id' => $constancia]);
        }

        $this->reemplazarRequisito(self::REQUISITO_ANTERIOR, self::REQUISITO_ACTUAL);
        $this->crearProgramas();
        $this->crearAdjuntos();
        $this->comentar();
    }

    public function down(): void
    {
        Schema::dropIfExists('tra_adjuntos');
        Schema::dropIfExists('tra_tipo_tramite_programa');
        DB::unprepared('DROP FUNCTION IF EXISTS tra_impedir_cambio_de_adjuntos();');
        $this->reemplazarRequisito(self::REQUISITO_ACTUAL, self::REQUISITO_ANTERIOR);

        DB::statement('ALTER TABLE tra_tipos_tramite DROP CONSTRAINT tra_tipos_tramite_fut_check');
        Schema::table('tra_tipos_tramite', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('requiere_tipo_previo_id');
            $table->dropColumn(['campos', 'adjuntos']);
        });
    }

    private function crearProgramas(): void
    {
        Schema::create('tra_tipo_tramite_programa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tipo_tramite_id')->constrained('tra_tipos_tramite')->restrictOnDelete();
            $table->foreignId('programa_id')->constrained('aca_programas')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['tipo_tramite_id', 'programa_id']);
            $table->index('programa_id');
        });

        DB::unprepared('CREATE TRIGGER tra_tipo_tramite_programa_auditoria AFTER INSERT OR UPDATE OR DELETE ON tra_tipo_tramite_programa FOR EACH ROW EXECUTE FUNCTION aud_registrar();');
    }

    // Attachments are evidence of the request: never edited nor deleted, like its history.
    private function crearAdjuntos(): void
    {
        Schema::create('tra_adjuntos', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('tramite_id')->constrained('tra_tramites')->restrictOnDelete();
            $table->string('nombre', 80);
            $table->string('path');
            $table->char('sha256', 64);
            $table->integer('bytes');
            $table->string('mime', 80);
            $table->timestampsTz();

            $table->index('tramite_id');
        });

        DB::statement('ALTER TABLE tra_adjuntos ADD CONSTRAINT tra_adjuntos_bytes_check CHECK (bytes BETWEEN 1 AND 5242880)');
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION tra_impedir_cambio_de_adjuntos() RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = pg_catalog, public, pg_temp
            AS $$
            BEGIN
                RAISE EXCEPTION 'tra_adjuntos: los adjuntos de un trámite no se modifican ni se eliminan' USING ERRCODE = 'check_violation';
            END;
            $$;
            CREATE TRIGGER tra_adjuntos_bloqueo BEFORE UPDATE OR DELETE ON tra_adjuntos FOR EACH ROW EXECUTE FUNCTION tra_impedir_cambio_de_adjuntos();
            CREATE TRIGGER tra_adjuntos_sin_truncate BEFORE TRUNCATE ON tra_adjuntos FOR EACH STATEMENT EXECUTE FUNCTION tra_impedir_truncate();
            CREATE TRIGGER tra_adjuntos_auditoria AFTER INSERT OR UPDATE OR DELETE ON tra_adjuntos FOR EACH ROW EXECUTE FUNCTION aud_registrar();
            SQL);
    }

    private function comentar(): void
    {
        DB::unprepared(<<<'SQL'
            COMMENT ON COLUMN tra_tipos_tramite.campos IS 'Campos del FUT que pide el tipo: lista de {clave, obligatorio} con claves matricula, idioma, nivel, programa, mencion, exposicion, celular o motivo.';
            COMMENT ON COLUMN tra_tipos_tramite.adjuntos IS 'Archivos que pide el tipo: lista de hasta 5 {nombre, obligatorio}.';
            COMMENT ON COLUMN tra_tipos_tramite.requiere_tipo_previo_id IS 'Tipo de trámite que la persona debe tener finalizado para el mismo idioma y nivel antes de solicitar este.';
            COMMENT ON TABLE tra_tipo_tramite_programa IS 'Programas cuyos estudiantes pueden solicitar un tipo de trámite; sin filas, lo solicita cualquiera.';
            COMMENT ON COLUMN tra_tipo_tramite_programa.id IS 'Identificador interno (llave primaria).';
            COMMENT ON COLUMN tra_tipo_tramite_programa.tipo_tramite_id IS 'Tipo de trámite restringido. Referencia a tra_tipos_tramite.';
            COMMENT ON COLUMN tra_tipo_tramite_programa.programa_id IS 'Programa autorizado. Referencia a aca_programas.';
            COMMENT ON COLUMN tra_tipo_tramite_programa.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN tra_tipo_tramite_programa.updated_at IS 'Fecha y hora de la última modificación.';
            COMMENT ON TABLE tra_adjuntos IS 'Archivos que el estudiante adjunta a su trámite, guardados en el disco privado; no se modifican ni se eliminan.';
            COMMENT ON COLUMN tra_adjuntos.id IS 'Identificador interno (llave primaria).';
            COMMENT ON COLUMN tra_adjuntos.ulid IS 'Identificador público usado en el enlace de descarga.';
            COMMENT ON COLUMN tra_adjuntos.tramite_id IS 'Trámite al que pertenece. Referencia a tra_tramites.';
            COMMENT ON COLUMN tra_adjuntos.nombre IS 'Nombre del adjunto pedido por el tipo de trámite al momento de la solicitud.';
            COMMENT ON COLUMN tra_adjuntos.path IS 'Ruta del archivo en el disco privado.';
            COMMENT ON COLUMN tra_adjuntos.sha256 IS 'Huella SHA-256 del archivo.';
            COMMENT ON COLUMN tra_adjuntos.bytes IS 'Tamaño del archivo en bytes (máximo 5 MB).';
            COMMENT ON COLUMN tra_adjuntos.mime IS 'Tipo MIME real del archivo (PDF, JPG o PNG).';
            COMMENT ON COLUMN tra_adjuntos.created_at IS 'Fecha y hora de registro.';
            COMMENT ON COLUMN tra_adjuntos.updated_at IS 'Fecha y hora de la última modificación.';
            SQL);
    }

    private function reemplazarRequisito(string $de, string $a): void
    {
        DB::table('tra_tipos_tramite')->where(['codigo' => 'CERTIFICADO-NIVEL', 'requisitos' => $de])->update(['requisitos' => $a, 'updated_at' => now()]);
    }
};
