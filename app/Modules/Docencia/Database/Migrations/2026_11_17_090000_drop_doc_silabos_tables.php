<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The CILN does not use a syllabus; down() rebuilds the tables as 2026_10_31_130000, 2026_11_01_090000 and the data dictionary left them.
return new class extends Migration
{
    private const array COMENTARIOS = [
        'doc_silabos' => [
            '@' => 'Sílabo de cada grupo.',
            'id' => 'Identificador interno (llave primaria).',
            'grupo_id' => 'Grupo del sílabo.',
            'estado' => 'Estado del sílabo: borrador o publicado.',
            'sumilla' => 'Sumilla del curso.',
            'competencias' => 'Competencias.',
            'capacidades' => 'Capacidades.',
            'metodologia' => 'Metodología.',
            'bibliografia' => 'Bibliografía.',
            'publicado_en' => 'Fecha y hora de publicación.',
            'publicado_por' => 'Cuenta que publicó el sílabo.',
            'created_at' => 'Fecha y hora de creación del registro.',
            'updated_at' => 'Fecha y hora de la última modificación del registro.',
        ],
        'doc_silabo_semanas' => [
            '@' => 'Programación semanal del sílabo.',
            'id' => 'Identificador interno (llave primaria).',
            'silabo_id' => 'Sílabo.',
            'semana' => 'Número de semana.',
            'unidad' => 'Unidad didáctica.',
            'saber' => 'Contenidos conceptuales (saber).',
            'saber_hacer' => 'Contenidos procedimentales (saber hacer).',
            'saber_ser' => 'Contenidos actitudinales (saber ser).',
            'actividades' => 'Actividades.',
            'recursos' => 'Recursos.',
            'created_at' => 'Fecha y hora de creación del registro.',
            'updated_at' => 'Fecha y hora de la última modificación del registro.',
        ],
        'doc_silabo_evaluaciones' => [
            '@' => 'Evaluaciones programadas en el sílabo.',
            'id' => 'Identificador interno (llave primaria).',
            'silabo_id' => 'Sílabo.',
            'componente_codigo' => 'Código del componente de evaluación.',
            'indicadores' => 'Indicadores de logro.',
            'instrumento' => 'Instrumento de evaluación.',
            'semana' => 'Semana en que se evalúa.',
            'created_at' => 'Fecha y hora de creación del registro.',
            'updated_at' => 'Fecha y hora de la última modificación del registro.',
        ],
        'doc_clases_dictadas' => [
            'silabo_semana_id' => 'Semana del sílabo que corresponde a la clase.',
        ],
    ];

    public function up(): void
    {
        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('silabo_semana_id');
        });

        Schema::dropIfExists('doc_silabo_evaluaciones');
        Schema::dropIfExists('doc_silabo_semanas');
        Schema::dropIfExists('doc_silabos');
    }

    public function down(): void
    {
        $this->crearSilabos();
        $this->crearSemanas();
        $this->crearEvaluaciones();

        foreach (['doc_silabos', 'doc_silabo_semanas', 'doc_silabo_evaluaciones'] as $tabla) {
            DB::unprepared("CREATE TRIGGER {$tabla}_auditoria AFTER INSERT OR UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION aud_registrar();");
        }

        Schema::table('doc_clases_dictadas', function (Blueprint $table): void {
            $table->foreignId('silabo_semana_id')->nullable()->constrained('doc_silabo_semanas')->nullOnDelete();

            $table->index('silabo_semana_id');
        });

        $this->comentar();
    }

    private function crearSilabos(): void
    {
        Schema::create('doc_silabos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grupo_id')->unique()->constrained('aca_grupos')->restrictOnDelete();
            $table->enum('estado', ['borrador', 'publicado'])->default('borrador');
            $table->text('sumilla')->nullable();
            $table->text('competencias')->nullable();
            $table->text('capacidades')->nullable();
            $table->text('metodologia')->nullable();
            $table->text('bibliografia')->nullable();
            $table->timestampTz('publicado_en')->nullable();
            $table->foreignId('publicado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->index('estado');
            $table->index('publicado_por');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE doc_silabos ADD CONSTRAINT doc_silabos_publicacion_check
                CHECK ((estado = 'publicado') = (publicado_en IS NOT NULL AND publicado_por IS NOT NULL))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE doc_silabos ADD CONSTRAINT doc_silabos_longitud_check CHECK (
                char_length(coalesce(sumilla, '')) <= 2000 AND char_length(coalesce(competencias, '')) <= 2000
                AND char_length(coalesce(capacidades, '')) <= 2000 AND char_length(coalesce(metodologia, '')) <= 2000
                AND char_length(coalesce(bibliografia, '')) <= 2000)
            SQL);
    }

    private function crearSemanas(): void
    {
        Schema::create('doc_silabo_semanas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('silabo_id')->constrained('doc_silabos')->restrictOnDelete();
            $table->smallInteger('semana');
            $table->string('unidad', 500)->nullable();
            $table->string('saber', 500)->nullable();
            $table->string('saber_hacer', 500)->nullable();
            $table->string('saber_ser', 500)->nullable();
            $table->string('actividades', 500)->nullable();
            $table->string('recursos', 500)->nullable();
            $table->timestampsTz();

            $table->unique(['silabo_id', 'semana']);
        });

        DB::statement('ALTER TABLE doc_silabo_semanas ADD CONSTRAINT doc_silabo_semanas_semana_check CHECK (semana >= 1)');
    }

    private function crearEvaluaciones(): void
    {
        Schema::create('doc_silabo_evaluaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('silabo_id')->constrained('doc_silabos')->restrictOnDelete();
            $table->string('componente_codigo', 30);
            $table->string('indicadores', 500)->nullable();
            $table->string('instrumento', 500)->nullable();
            $table->smallInteger('semana')->nullable();
            $table->timestampsTz();

            $table->unique(['silabo_id', 'componente_codigo']);
        });

        DB::statement('ALTER TABLE doc_silabo_evaluaciones ADD CONSTRAINT doc_silabo_evaluaciones_semana_check CHECK (semana IS NULL OR semana >= 1)');
    }

    private function comentar(): void
    {
        $pdo = DB::getPdo();

        foreach (self::COMENTARIOS as $tabla => $columnas) {
            foreach ($columnas as $columna => $texto) {
                DB::statement($columna === '@'
                    ? sprintf('COMMENT ON TABLE %s IS %s', $tabla, $pdo->quote($texto))
                    : sprintf('COMMENT ON COLUMN %s.%s IS %s', $tabla, $columna, $pdo->quote($texto)));
            }
        }
    }
};
